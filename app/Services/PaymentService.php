<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\OrderConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PaymentService
{
    /**
     * Verify a reference against the gateway and, if successful, mark the
     * order paid. Idempotent — safe to call from both the redirect
     * callback and the webhook for the same reference.
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
            $order->refresh();

            if ($order->isPaid()) {
                return $order;
            }

            $payment = Payment::firstOrNew(['reference' => $reference]);
            $payment->order_id = $order->id;
            $payment->gateway = $gateway->key();
            $payment->amount_kobo = $result['amount_kobo'];
            $payment->currency = $result['currency'];
            $payment->gateway_response = $result['raw'];

            if (! $result['success'] || $result['amount_kobo'] < $order->total_kobo) {
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

            $payment->status = Payment::STATUS_SUCCESSFUL;
            $payment->paid_at = now();
            $payment->save();

            $order->update([
                'payment_gateway' => $gateway->key(),
                'payment_reference' => $reference,
                'paid_at' => now(),
            ]);

            $order->recordStatus(Order::STATUS_PROCESSING, 'Payment confirmed via '.$gateway->label().'.');

            if ($order->discount_code) {
                DiscountCode::where('code', $order->discount_code)->first()?->incrementUsage();
            }

            if ($order->user) {
                $order->user->notify(new OrderConfirmed($order));
            } elseif ($order->guest_email) {
                Notification::route('mail', $order->guest_email)->notify(new OrderConfirmed($order));
            }

            return $order;
        });
    }
}
