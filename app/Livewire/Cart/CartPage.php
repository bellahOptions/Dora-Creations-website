<?php

namespace App\Livewire\Cart;

use App\Models\CartItem;
use App\Models\SiteSetting;
use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartPage extends Component
{
    #[On('cart-updated')]
    public function refresh(): void
    {
        //
    }

    public function incrementItem(int $itemId, CartService $cartService): void
    {
        $item = CartItem::whereKey($itemId)->firstOrFail();
        $cartService->updateQuantity($item, $item->quantity + 1);
        $this->dispatch('cart-updated');
    }

    public function decrementItem(int $itemId, CartService $cartService): void
    {
        $item = CartItem::whereKey($itemId)->firstOrFail();
        $cartService->updateQuantity($item, $item->quantity - 1);
        $this->dispatch('cart-updated');
    }

    public function removeItem(int $itemId, CartService $cartService): void
    {
        $item = CartItem::whereKey($itemId)->firstOrFail();
        $cartService->removeItem($item);
        $this->dispatch('cart-updated');
    }

    public function render(CartService $cartService)
    {
        $cart = $cartService->currentCart();
        $cart->load(['items.product.images', 'items.variant']);

        $settings = SiteSetting::current();
        $subtotal = $cart->subtotalKobo();
        $freeShippingThreshold = $settings->free_shipping_threshold_kobo;
        $shipping = $freeShippingThreshold && $subtotal >= $freeShippingThreshold ? 0 : $settings->shipping_flat_rate_kobo;

        return view('livewire.cart.cart-page', [
            'cart' => $cart,
            'shippingKobo' => $shipping,
            'freeShippingThreshold' => $freeShippingThreshold,
        ]);
    }
}
