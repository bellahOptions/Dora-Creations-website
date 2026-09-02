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
     * @return array<string, PaymentGateway>
     */
    public function all(): array
    {
        return $this->gateways;
    }
}
