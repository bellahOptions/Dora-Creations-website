<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A cart that cannot be turned into an order for a reason the customer can
 * act on (something sold out, or was delisted while it sat in their cart).
 *
 * Checkout catches these and shows `userMessage()` — the exception message
 * itself stays technical for the logs.
 */
class CheckoutException extends RuntimeException
{
    public function __construct(string $message, protected readonly ?string $customerMessage = null)
    {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        return $this->customerMessage ?? $this->getMessage();
    }
}
