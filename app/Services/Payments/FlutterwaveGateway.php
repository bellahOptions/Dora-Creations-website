<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveGateway implements PaymentGateway
{
    protected string $baseUrl = 'https://api.flutterwave.com/v3';

    public function key(): string
    {
        return 'flutterwave';
    }

    public function label(): string
    {
        return 'Flutterwave';
    }

    public function initialize(Order $order, string $callbackUrl): array
    {
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->post("{$this->baseUrl}/payments", [
                'tx_ref' => $order->order_number,
                'amount' => Money::naira($order->total_kobo),
                'currency' => 'NGN',
                'redirect_url' => $callbackUrl,
                'customer' => [
                    'email' => $order->customerEmail(),
                    'name' => $order->customerName(),
                    'phonenumber' => $order->shipping_phone,
                ],
                'customizations' => [
                    'title' => 'Dora Creations',
                    'description' => "Order {$order->order_number}",
                ],
                'meta' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ])
            ->throw();

        $data = $response->json('data');

        return [
            'redirect_url' => $data['link'],
            'reference' => $order->order_number,
        ];
    }

    public function verify(string $reference): array
    {
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->get("{$this->baseUrl}/transactions/verify_by_reference", ['tx_ref' => $reference]);

        if (! $response->successful()) {
            Log::warning('Flutterwave verify request failed', ['reference' => $reference, 'status' => $response->status()]);

            return ['success' => false, 'checked' => false, 'amount_kobo' => 0, 'currency' => 'NGN', 'raw' => $response->json() ?? []];
        }

        $data = $response->json('data', []);

        return [
            'success' => ($data['status'] ?? null) === 'successful',
            'checked' => true,
            'amount_kobo' => Money::kobo((float) ($data['amount'] ?? 0)),
            'currency' => $data['currency'] ?? 'NGN',
            'raw' => $data,
        ];
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('verif-hash');
        $expected = config('services.flutterwave.secret_hash');

        if (! $signature || ! $expected) {
            return false;
        }

        return hash_equals((string) $expected, $signature);
    }

    public function referenceFromWebhook(Request $request): ?string
    {
        return $request->input('data.tx_ref') ?? $request->input('txRef');
    }

    public function refund(string $reference): bool
    {
        $verification = $this->verify($reference);
        $transactionId = $verification['raw']['id'] ?? null;

        if (! $transactionId) {
            return false;
        }

        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->post("{$this->baseUrl}/transactions/{$transactionId}/refund");

        return $response->successful();
    }
}
