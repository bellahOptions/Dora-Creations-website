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
                'metadata' => $this->metadataFor($order),
            ])
            ->throw();

        $data = $response->json('data');

        return [
            'redirect_url' => $data['authorization_url'],
            'reference' => $data['reference'],
        ];
    }

    /**
     * Everything the studio knows about the buyer, sent along with the
     * transaction.
     *
     * Paystack's initialize endpoint only takes `email` as a top-level
     * customer field — `name` and `phone` there are ignored. `metadata` (and
     * the `custom_fields` inside it) is the documented way to attach them:
     * they show against the transaction in the Paystack dashboard and come
     * back on every webhook, which is what lets a payment be matched to a
     * person and a delivery address.
     *
     * @return array<string, mixed>
     */
    protected function metadataFor(Order $order): array
    {
        $name = trim((string) $order->customerName());
        $email = trim((string) $order->customerEmail());
        $phone = trim((string) $order->shipping_phone);

        [$firstName, $lastName] = array_pad(preg_split('/\s+/', $name, 2) ?: [], 2, null);

        return array_filter([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'custom_fields' => array_values(array_filter([
                $name !== '' ? ['display_name' => 'Customer name', 'variable_name' => 'customer_name', 'value' => $name] : null,
                $phone !== '' ? ['display_name' => 'Phone', 'variable_name' => 'customer_phone', 'value' => $phone] : null,
                $email !== '' ? ['display_name' => 'Email', 'variable_name' => 'customer_email', 'value' => $email] : null,
            ])),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    public function verify(string $reference): array
    {
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get("{$this->baseUrl}/transaction/verify/".rawurlencode($reference));

        if (! $response->successful()) {
            Log::warning('Paystack verify request failed', ['reference' => $reference, 'status' => $response->status()]);

            return ['success' => false, 'checked' => false, 'amount_kobo' => 0, 'currency' => 'NGN', 'raw' => $response->json() ?? []];
        }

        $data = $response->json('data', []);

        return [
            'success' => ($data['status'] ?? null) === 'success',
            'checked' => true,
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
