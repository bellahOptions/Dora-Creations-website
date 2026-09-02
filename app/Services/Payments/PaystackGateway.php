<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackGateway implements PaymentGateway
{
    protected string $baseUrl = 'https://api.paystack.co';

    public function key(): string
    {
        return 'paystack';
    }

    public function label(): string
    {
        return 'Paystack';
    }

    public function initialize(Order $order, string $callbackUrl): array
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email' => $order->customerEmail(),
                'amount' => $order->total_kobo,
                'currency' => 'NGN',
                'reference' => $order->order_number,
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ])
            ->throw();

        $data = $response->json('data');

        return [
            'redirect_url' => $data['authorization_url'],
            'reference' => $data['reference'],
        ];
    }

    public function verify(string $reference): array
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get("{$this->baseUrl}/transaction/verify/".rawurlencode($reference));

        if (! $response->successful()) {
            Log::warning('Paystack verify request failed', ['reference' => $reference, 'status' => $response->status()]);

            return ['success' => false, 'amount_kobo' => 0, 'currency' => 'NGN', 'raw' => $response->json() ?? []];
        }

        $data = $response->json('data', []);

        return [
            'success' => ($data['status'] ?? null) === 'success',
            'amount_kobo' => (int) ($data['amount'] ?? 0),
            'currency' => $data['currency'] ?? 'NGN',
            'raw' => $data,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Paystack-Signature');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha512', $request->getContent(), (string) config('services.paystack.secret_key'));

        return hash_equals($expected, $signature);
    }

    public function referenceFromWebhook(Request $request): ?string
    {
        return $request->input('data.reference');
    }

    public function refund(string $reference): bool
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post("{$this->baseUrl}/refund", ['transaction' => $reference]);

        return $response->successful();
    }
}
