<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Cart;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * @param  array{label?: string, full_name: string, phone: string, state: string, city: string, line1: string, line2?: string, postal_code?: string}  $shipping
     * @param  string|null  $paymentGateway  Only set up-front for bank transfer,
     *                                       which has no gateway callback to
     *                                       stamp it later.
     *
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function createFromCart(
        Cart $cart,
        array $shipping,
        ?User $user,
        ?string $guestEmail,
        ?string $customerNote = null,
        ?string $discountCode = null,
        ?string $paymentGateway = null,
    ): Order {
        if ($user?->is_admin) {
            throw new \RuntimeException('Admin accounts cannot place orders.');
        }

        $cart->loadMissing(['items.product', 'items.variant']);

        $settings = SiteSetting::current();
        $subtotal = $cart->subtotalKobo();
        $freeShippingThreshold = $settings->free_shipping_threshold_kobo;
        $shippingKobo = $freeShippingThreshold && $subtotal >= $freeShippingThreshold
            ? 0
            : $settings->shipping_flat_rate_kobo;

        // Re-validated here, server-side, against the authoritative cart
        // total — never trust a discount amount computed earlier in the
        // request (the code could have expired or hit its cap since).
        $discount = $discountCode ? DiscountCode::findValid($discountCode, $subtotal) : null;

        if ($discountCode && ! $discount) {
            throw new CheckoutException(
                "Discount code {$discountCode} is no longer valid.",
                'That discount code is no longer valid. Please remove it and try again.',
            );
        }

        // Claim the code's use inside this transaction so a capped code can't
        // be applied to a burst of orders before any of them pays; the claim
        // is handed back if the order later fails.
        if ($discount && ! $discount->reserveUsage()) {
            throw new CheckoutException(
                "Discount code {$discountCode} has reached its usage limit.",
                'That discount code has just been fully redeemed. Please remove it and try again.',
            );
        }

        $discountKobo = $discount?->calculateDiscount($subtotal) ?? 0;

        // An item whose size/colour couldn't be chosen (no options configured
        // yet) can still be bought — the studio confirms it afterwards, which
        // is why those orders get a longer delivery estimate.
        $needsVariantConfirmation = $cart->items->contains(
            fn ($item) => ! $item->product_variant_id && $item->product?->has_variants,
        );

        $deliveryDays = $settings->deliveryLeadDays()
            + ($needsVariantConfirmation ? Order::EXTRA_DAYS_WITHOUT_VARIANT : 0);

        return DB::transaction(function () use ($cart, $shipping, $user, $guestEmail, $customerNote, $subtotal, $shippingKobo, $discount, $discountKobo, $paymentGateway, $needsVariantConfirmation, $deliveryDays) {
            // Any earlier unpaid card checkout from this same cart is
            // superseded: the shopper went back to edit, so the stale attempt's
            // stock hold is released now instead of being left to rot until the
            // reconciler sweeps it. Bank transfer orders are excluded — that
            // money may already be on its way and an admin still has to be able
            // to confirm it. Done inside the transaction so a failed attempt
            // here (e.g. sold out) leaves the previous one alone.
            Order::query()
                ->where('cart_id', $cart->id)
                ->whereNull('payment_gateway')
                ->where('status', Order::STATUS_PENDING_PAYMENT)
                ->whereNull('paid_at')
                ->get()
                ->each(fn (Order $superseded) => $superseded->recordStatus(
                    Order::STATUS_PAYMENT_FAILED,
                    'Superseded: the customer went back and checked out again from the same cart.',
                    null,
                    notify: false,
                ));

            $order = Order::create([
                'user_id' => $user?->id,
                'guest_email' => $user ? null : $guestEmail,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'display_currency' => session('currency', 'NGN'),
                'subtotal_kobo' => $subtotal,
                'discount_code' => $discount?->code,
                'discount_kobo' => $discountKobo,
                'shipping_kobo' => $shippingKobo,
                'total_kobo' => max(0, $subtotal + $shippingKobo - $discountKobo),
                'shipping_full_name' => $shipping['full_name'],
                'shipping_phone' => $shipping['phone'],
                'shipping_country' => 'Nigeria',
                'shipping_state' => $shipping['state'],
                'shipping_city' => $shipping['city'],
                'shipping_line1' => $shipping['line1'],
                'shipping_line2' => $shipping['line2'] ?? null,
                'shipping_postal_code' => $shipping['postal_code'] ?? null,
                'payment_gateway' => $paymentGateway,
                'customer_note' => $customerNote,
                'cart_id' => $cart->id,
                'needs_variant_confirmation' => $needsVariantConfirmation,
                'estimated_delivery_at' => now()->addDays($deliveryDays),
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;

                // A product can be delisted while it sits in someone's cart.
                // Never let that become an order.
                if (! $product->is_published) {
                    throw new CheckoutException(
                        "Product {$product->id} ({$product->name}) is no longer published.",
                        "\"{$product->name}\" is no longer available. Please remove it from your cart and try again.",
                    );
                }

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product->name,
                    'variant_label' => $item->variant?->label(),
                    'unit_price_kobo' => $item->unit_price_kobo,
                    'quantity' => $item->quantity,
                    'is_preorder' => $item->product->is_preorder,
                    'line_total_kobo' => $item->lineTotalKobo(),
                ]);
            }

            // Holds the stock for this order. Throws — rolling the whole
            // order back — if any line has sold out since it was added to
            // the cart, so we never take money we can't fulfil.
            app(OrderStockService::class)->reserve($order);

            $order->statusHistories()->create(['status' => Order::STATUS_PENDING_PAYMENT]);

            ActivityLogger::visitor(
                ($user?->name ?? $guestEmail ?? 'A guest').' placed order '.$order->order_number.' for '.Money::ngn($order->total_kobo).'.',
                $order,
            );

            return $order;
        });
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
