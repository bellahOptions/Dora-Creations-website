<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    protected array $gateways;

    public function __construct(PaystackGateway $paystack, FlutterwaveGateway $flutterwave)
    {
        $this->gateways = [
            $paystack->key() => $paystack,
            $flutterwave->key() => $flutterwave,
        ];
    }

    public function get(string $key): PaymentGateway
    {
        return $this->gateways[$key] ?? throw new InvalidArgumentException("Unknown payment gateway [{$key}].");
    }

    /**
     * Whether a gateway key is real. Callers that take the key from the URL
     * check this so an unknown gateway is a 404, not a 500.
     */
    public function has(string $key): bool
    {
        return isset($this->gateways[$key]);
    }

    /**
     * @return array<string, PaymentGateway>
     */
    public function all(): array
    {
        return $this->gateways;
    }
}
