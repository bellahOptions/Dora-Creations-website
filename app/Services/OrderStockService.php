<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Takes stock off the shelf when an order is placed, and puts it back if that
 * order is never paid for.
 *
 * Ordering is the right moment to hold stock: paying happens on an external
 * gateway, so decrementing only once payment lands would let two shoppers pay
 * for the same last item. Each decrement is a single conditional UPDATE
 * (`where stock >= qty`), so concurrent checkouts can't both win the same
 * unit — the loser gets zero affected rows and the order is refused.
 */
class OrderStockService
{
    /**
     * Reserve stock for every line on the order.
     *
     * Must be called inside the same transaction that created the order, so a
     * failure on any line rolls the whole order back rather than leaving it
     * holding half its stock.
     *
     * @throws InsufficientStockException
     */
    public function reserve(Order $order): void
    {
        // Already holding stock for this order. An order whose hold was
        // released (it was marked payment failed, then the money turned up
        // anyway) does need to be re-held, so released orders fall through.
        if ($order->stock_reserved_at !== null && $order->stock_released_at === null) {
            return;
        }

        $order->load(['items.variant', 'items.product']);

        foreach ($order->items as $item) {
            // Pre-orders are explicitly sold ahead of stock.
            if ($item->is_preorder) {
                continue;
            }

            // An item bought without choosing an option (none were set up)
            // has no per-variant stock to hold, and the product's own stock
            // column is unused while variants are enabled. Nothing to take —
            // the studio confirms the option before making it up.
            if (! $item->product_variant_id && $item->product?->has_variants) {
                continue;
            }

            $quantity = (int) $item->quantity;

            $taken = $item->product_variant_id
                ? ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->where('stock_quantity', '>=', $quantity)
                    ->decrement('stock_quantity', $quantity)
                : Product::query()
                    ->whereKey($item->product_id)
                    ->where('stock_quantity', '>=', $quantity)
                    ->decrement('stock_quantity', $quantity);

            if ($taken === 0) {
                throw new InsufficientStockException($item->product_name, $item->variant_label);
            }
        }

        $order->forceFill(['stock_reserved_at' => now(), 'stock_released_at' => null])->save();

        // The decrements above bypass model events, so the storefront's
        // cached listings would otherwise keep advertising sold-out stock.
        StorefrontCache::flush();
    }

    /**
     * Hand reserved stock back — used when an order is never paid for.
     * Idempotent: releasing twice can't inflate stock.
     *
     * @return bool  whether this call actually did the release, so callers can
     *               tie other one-shot compensations to the same moment.
     */
    public function release(Order $order): bool
    {
        if ($order->stock_reserved_at === null || $order->stock_released_at !== null) {
            return false;
        }

        $order->load('items');

        foreach ($order->items as $item) {
            if ($item->is_preorder) {
                continue;
            }

            if (! $item->product_variant_id && $item->product?->has_variants) {
                continue;
            }

            $quantity = (int) $item->quantity;

            if ($item->product_variant_id) {
                ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->increment('stock_quantity', $quantity);
            } else {
                Product::query()
                    ->whereKey($item->product_id)
                    ->increment('stock_quantity', $quantity);
            }
        }

        $order->forceFill(['stock_released_at' => now()])->save();

        StorefrontCache::flush();

        return true;
    }
}

