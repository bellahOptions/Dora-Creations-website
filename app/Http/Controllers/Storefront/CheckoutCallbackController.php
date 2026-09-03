<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutCallbackController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentGatewayManager $gateways, PaymentService $paymentService): RedirectResponse
    {
        $reference = $request->query('reference')
            ?? $request->query('trxref')
            ?? $request->query('tx_ref');

        if (! $reference) {
            return redirect()->route('cart.index')->with('checkout-error', 'We could not confirm your payment reference.');
        }

        $order = $paymentService->confirm($gateways->get($gateway), $reference);

        if (! $order || ! $order->isPaid()) {
            return redirect()->route('checkout.index')->with('checkout-error', 'Your payment could not be confirmed. Please try again.');
        }

        return redirect()->route('order-tracking.show', $order->public_token)
            ->with('order-confirmed', true);
    }
}
