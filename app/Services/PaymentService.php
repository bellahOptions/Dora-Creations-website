<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OrderConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PaymentService
{
    /**
     * Verify a reference against the gateway and, if successful, mark the
     * order paid. Idempotent — safe to call from both the redirect
     * callback and the webhook for the same reference, and safe to call
     * twice concurrently: the order row is locked for the duration so only
     * one caller can move it from unpaid to paid.
     */
    public function confirm(PaymentGateway $gateway, string $reference): ?Order
    {
        $order = Order::where('order_number', $reference)->first();

        if (! $order) {
            Log::warning('Payment confirmation for unknown order reference', ['reference' => $reference, 'gateway' => $gateway->key()]);

            return null;
        }

        if ($order->isPaid()) {
            return $order;
        }

        $result = $gateway->verify($reference);

        return DB::transaction(function () use ($gateway, $reference, $order, $result) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->isPaid()) {
                return $order;
            }

            if (! $result['success']
                || $result['amount_kobo'] < $order->total_kobo
                // Both gateways are initialised in NGN; anything else is a
                // different unit of value and must never settle this order.
                || strtoupper((string) $result['currency']) !== 'NGN') {
                $payment = $this->paymentFor($order, $reference);
                $payment->gateway = $gateway->key();
                $payment->amount_kobo = $result['amount_kobo'];
                $payment->currency = $result['currency'];
                $payment->gateway_response = $result['raw'];
                $payment->status = Payment::STATUS_FAILED;
                $payment->save();

                Log::warning('Payment verification failed or amount mismatch', [
                    'order' => $order->order_number,
                    'expected_kobo' => $order->total_kobo,
                    'received_kobo' => $result['amount_kobo'],
                ]);

                // Only mark the order itself failed once the gateway has given us
                // a definitive answer — a network blip during verification isn't
                // proof the payment failed, so we leave those for a later retry.
                if (($result['checked'] ?? false) && $order->status === Order::STATUS_PENDING_PAYMENT) {
                    $order->recordStatus(Order::STATUS_PAYMENT_FAILED, 'No successful payment confirmed via '.$gateway->label().'.');
                }

                return $order;
            }

            $this->settle(
                order: $order,
                gateway: $gateway->key(),
                reference: $reference,
                amountKobo: $result['amount_kobo'],
                currency: $result['currency'],
                raw: $result['raw'],
                note: 'Payment confirmed via '.$gateway->label().'.',
            );

            return $order;
        });
    }

    /**
     * An admin confirming that a customer's direct bank transfer landed.
     *
     * The storefront only ever records the customer's *claim* that they paid;
     * money is never treated as received until a human has checked the bank
     * and run this. Returns false when the order was already paid, so a
     * double-click can't double-count the sale.
     */
    public function markBankTransferReceived(Order $order, User $admin, ?string $note = null): bool
    {
        if ($order->isPaid()) {
            return false;
        }

        return (bool) DB::transaction(function () use ($order, $admin, $note) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->isPaid()) {
                return false;
            }

            $this->settle(
                order: $order,
                gateway: Order::GATEWAY_BANK_TRANSFER,
                reference: $order->order_number,
                amountKobo: $order->total_kobo,
                currency: 'NGN',
                raw: [
                    'confirmed_by_admin_id' => $admin->id,
                    'confirmed_by_admin_name' => $admin->name,
                    'note' => $note,
                    'confirmed_at' => now()->toIso8601String(),
                ],
                note: $note ?: 'Bank transfer confirmed by '.$admin->name.'.',
                changedBy: $admin,
            );

            return true;
        });
    }

    /**
     * The single place an order is marked paid: writes the payment row,
     * stamps the order, advances the status, burns the discount code and
     * tells the customer. Both the gateway path and the manual bank-transfer
     * path funnel through here so they can't drift apart.
     *
     * @param  array<string, mixed>  $raw
     */
    protected function settle(
        Order $order,
        string $gateway,
        string $reference,
        int $amountKobo,
        string $currency,
        array $raw,
        string $note,
        ?User $changedBy = null,
    ): void {
        // An order that was already marked payment-failed handed its stock
        // back. If the money turns up anyway — the customer finished paying on
        // a checkout page that was still open — we must take that stock again
        // before calling the order paid, or the same unit ends up both back on
        // the shelf and sold.
        $stockWarning = null;

        if ($order->stock_released_at !== null) {
            try {
                app(OrderStockService::class)->reserve($order->fresh());
                $order->refresh();
            } catch (InsufficientStockException $e) {
                // The payment is real, so we still record it rather than
                // silently dropping the customer's money. A human has to sort
                // this one out, so make it loud in the order history.
                report($e);
                $stockWarning = $e->getMessage();
            }
        }

        $payment = $this->paymentFor($order, $reference);
        $payment->gateway = $gateway;
        $payment->amount_kobo = $amountKobo;
        $payment->currency = $currency;
        $payment->gateway_response = $raw;
        $payment->status = Payment::STATUS_SUCCESSFUL;
        $payment->paid_at = now();
        $payment->save();

        $order->update([
            'payment_gateway' => $gateway,
            'payment_reference' => $reference,
            'paid_at' => now(),
        ]);

        $order->recordStatus(Order::STATUS_PROCESSING, $note, $changedBy);

        if ($stockWarning !== null) {
            $order->statusHistories()->create([
                'status' => Order::STATUS_PROCESSING,
                'note' => "WARNING: payment arrived after this order's stock had been released and it could not be re-held ({$stockWarning}) Resolve manually — refund or backorder.",
            ]);
        }

        // The discount code's use was already claimed when the order was
        // created, so there's nothing to burn here.
        $this->notifyOrderConfirmed($order);
    }

    protected function paymentFor(Order $order, string $reference): Payment
    {
        $payment = Payment::firstOrNew(['reference' => $reference]);
        $payment->order_id = $order->id;

        return $payment;
    }

    protected function notifyOrderConfirmed(Order $order): void
    {
        if ($order->user) {
            $order->user->notify(new OrderConfirmed($order));
        } elseif ($order->guest_email) {
            Notification::route('mail', $order->guest_email)->notify(new OrderConfirmed($order));
        }
    }
}
