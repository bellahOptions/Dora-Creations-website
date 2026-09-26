<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentGatewayManager $gateways, PaymentService $paymentService): Response
    {
        if (! $gateways->has($gateway)) {
            return response()->noContent(404);
        }

        $gatewayService = $gateways->get($gateway);

        if (! $gatewayService->verifyWebhookSignature($request)) {
            Log::warning('Rejected payment webhook with invalid signature', ['gateway' => $gateway]);

            return response()->noContent(401);
        }

        $reference = $gatewayService->referenceFromWebhook($request);

        if ($reference) {
            $paymentService->confirm($gatewayService, $reference);
        }

        return response()->noContent();
    }
}
