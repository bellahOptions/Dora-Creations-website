<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Console\Command;

/**
 * Catches interrupted checkouts: a customer who never made it back from
 * the gateway (closed the tab, lost connection, or the redirect/webhook
 * just never arrived) leaves their order stuck on "pending payment"
 * forever otherwise. This checks stale pending orders against both
 * gateways directly, using the order number as the reference — the same
 * value both gateways were given at checkout — so it doesn't need to
 * already know which one the customer picked.
 */
class ReconcilePendingPayments extends Command
{
    protected $signature = 'payments:reconcile
        {--grace=30 : Minutes to wait after an order is placed before checking it}
        {--stale=1440 : Minutes after which a still-unresolved order is marked failed regardless of gateway responses}';

    protected $description = 'Resolve pending orders left over from interrupted checkouts by checking Paystack and Flutterwave directly';

    public function handle(PaymentGatewayManager $gateways, PaymentService $paymentService): int
    {
        $grace = (int) $this->option('grace');
        $stale = (int) $this->option('stale');

        $orders = Order::query()
            ->where('status', Order::STATUS_PENDING_PAYMENT)
            ->where('created_at', '<=', now()->subMinutes($grace))
            ->get();

        $paid = 0;
        $failed = 0;

        foreach ($orders as $order) {
            $confirmedGateway = null;
            $allChecked = true;

            foreach (['paystack', 'flutterwave'] as $key) {
                $gateway = $gateways->get($key);
                $result = $gateway->verify($order->order_number);

                if ($result['success']) {
                    $confirmedGateway = $gateway;
                    break;
                }

                if (! ($result['checked'] ?? false)) {
                    $allChecked = false;
                }
            }

            if ($confirmedGateway) {
                $paymentService->confirm($confirmedGateway, $order->order_number);
                $paid++;

                continue;
            }

            $order->refresh();

            if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
                continue;
            }

            $pastStaleThreshold = $order->created_at->lte(now()->subMinutes($stale));

            if ($allChecked || $pastStaleThreshold) {
                $order->recordStatus(Order::STATUS_PAYMENT_FAILED, 'No successful payment was found after checking Paystack and Flutterwave.');
                $failed++;
            }
        }

        $this->info("Checked {$orders->count()} pending order(s): {$paid} confirmed paid, {$failed} marked payment failed.");

        return self::SUCCESS;
    }
}
