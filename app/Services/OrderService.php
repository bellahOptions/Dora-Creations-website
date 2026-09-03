<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * @param  array{label?: string, full_name: string, phone: string, state: string, city: string, line1: string, line2?: string, postal_code?: string}  $shipping
     */
    public function createFromCart(Cart $cart, array $shipping, ?User $user, ?string $guestEmail, ?string $customerNote = null): Order
    {
        if ($user?->is_admin) {
            throw new \RuntimeException('Admin accounts cannot place orders.');
        }

        $cart->loadMissing('items.product');

        $settings = SiteSetting::current();
        $subtotal = $cart->subtotalKobo();
        $freeShippingThreshold = $settings->free_shipping_threshold_kobo;
        $shippingKobo = $freeShippingThreshold && $subtotal >= $freeShippingThreshold
            ? 0
            : $settings->shipping_flat_rate_kobo;

        return DB::transaction(function () use ($cart, $shipping, $user, $guestEmail, $customerNote, $subtotal, $shippingKobo) {
            $order = Order::create([
                'user_id' => $user?->id,
                'guest_email' => $user ? null : $guestEmail,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'display_currency' => session('currency', 'NGN'),
                'subtotal_kobo' => $subtotal,
                'shipping_kobo' => $shippingKobo,
                'total_kobo' => $subtotal + $shippingKobo,
                'shipping_full_name' => $shipping['full_name'],
                'shipping_phone' => $shipping['phone'],
                'shipping_country' => 'Nigeria',
                'shipping_state' => $shipping['state'],
                'shipping_city' => $shipping['city'],
                'shipping_line1' => $shipping['line1'],
                'shipping_line2' => $shipping['line2'] ?? null,
                'shipping_postal_code' => $shipping['postal_code'] ?? null,
                'customer_note' => $customerNote,
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product->name,
                    'variant_label' => $item->variant?->label(),
                    'unit_price_kobo' => $item->unit_price_kobo,
                    'quantity' => $item->quantity,
                    'line_total_kobo' => $item->lineTotalKobo(),
                ]);
            }

            $order->statusHistories()->create(['status' => Order::STATUS_PENDING_PAYMENT]);

            return $order;
        });
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
