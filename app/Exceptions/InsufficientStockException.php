<?php

namespace App\Exceptions;

/**
 * Thrown when an order can't be placed because the stock taken by the
 * checkout is no longer there (someone else bought the last one first).
 */
class InsufficientStockException extends CheckoutException
{
    public function __construct(
        public readonly string $productName,
        public readonly ?string $variantLabel = null,
    ) {
        $item = $variantLabel ? "{$productName} ({$variantLabel})" : $productName;

        parent::__construct("Not enough stock for {$item}.");
    }

    /**
     * Customer-facing wording — safe to render on the checkout page.
     */
    public function userMessage(): string
    {
        $item = $this->variantLabel ? "{$this->productName} ({$this->variantLabel})" : $this->productName;

        return "Sorry, {$item} just sold out. Please adjust your cart and try again.";
    }
}
