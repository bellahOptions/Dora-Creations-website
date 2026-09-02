<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CartService
{
    public const COOKIE_NAME = 'cart_token';

    public function currentCart(): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $token = request()->cookie(self::COOKIE_NAME);

        if ($token) {
            $cart = Cart::where('cart_token', $token)->whereNull('user_id')->first();

            if ($cart) {
                return $cart;
            }
        }

        $token = (string) Str::uuid();

        Cookie::queue(Cookie::make(self::COOKIE_NAME, $token, 60 * 24 * 60));

        return Cart::create(['cart_token' => $token]);
    }

    public function addItem(Product $product, ?ProductVariant $variant, int $quantity = 1): CartItem
    {
        $cart = $this->currentCart();

        $existing = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        $unitPriceKobo = $variant?->priceKobo() ?? $product->price_kobo;

        if ($existing) {
            $existing->update(['quantity' => $existing->quantity + $quantity]);

            return $existing->fresh();
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price_kobo' => $unitPriceKobo,
        ]);
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $quantity]);
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function itemCount(): int
    {
        return $this->currentCart()->itemCount();
    }

    /**
     * Fold a guest cart into the authenticating user's cart, summing
     * quantities for lines that already exist on both.
     */
    public function mergeGuestCartIntoUser(User $user): void
    {
        $token = request()->cookie(self::COOKIE_NAME);

        if (! $token) {
            return;
        }

        $guestCart = Cart::where('cart_token', $token)->whereNull('user_id')->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $guestItem->quantity]);
            } else {
                $userCart->items()->create([
                    'product_id' => $guestItem->product_id,
                    'product_variant_id' => $guestItem->product_variant_id,
                    'quantity' => $guestItem->quantity,
                    'unit_price_kobo' => $guestItem->unit_price_kobo,
                ]);
            }
        }

        $guestCart->delete();
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }
}
