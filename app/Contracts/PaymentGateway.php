<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Machine key, e.g. "paystack" or "flutterwave".
     */
    public function key(): string;

    public function label(): string;

    /**
     * Start a hosted-page transaction for the order and return the URL to
     * redirect the customer to.
     *
     * @return array{redirect_url: string, reference: string}
     */
    public function initialize(Order $order, string $callbackUrl): array;

    /**
     * Verify a transaction reference against the gateway's API. "checked"
     * distinguishes a definitive answer (the gateway responded, and either
     * confirmed or denied the payment) from an inconclusive one (a network
     * failure or non-2xx response) — callers should only treat the payment
     * as failed when checked is true, never on an inconclusive result.
     *
     * @return array{success: bool, checked: bool, amount_kobo: int, currency: string, raw: array}
     */
    public function verify(string $reference): array;

    /**
     * Verify that an inbound webhook request really came from this gateway.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Pull the transaction reference out of a verified webhook payload.
     */
    public function referenceFromWebhook(Request $request): ?string;

    /**
     * Attempt a refund for a previously successful transaction. Returns
     * true if the gateway accepted the refund request.
     */
    public function refund(string $reference): bool;
}
