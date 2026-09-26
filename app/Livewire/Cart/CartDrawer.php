<?php

namespace App\Livewire\Cart;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    #[On('cart-updated')]
    public function refresh(): void
    {
        //
    }

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function incrementItem(int $itemId, CartService $cartService): void
    {
        $item = $cartService->findOwnedItem($itemId);
        $cartService->updateQuantity($item, $item->quantity + 1);
        $this->dispatch('cart-updated');
    }

    public function decrementItem(int $itemId, CartService $cartService): void
    {
        $item = $cartService->findOwnedItem($itemId);
        $cartService->updateQuantity($item, $item->quantity - 1);
        $this->dispatch('cart-updated');
    }

    public function removeItem(int $itemId, CartService $cartService): void
    {
        $item = $cartService->findOwnedItem($itemId);
        $cartService->removeItem($item);
        $this->dispatch('cart-updated');
    }

    public function render(CartService $cartService)
    {
        $cart = $cartService->currentCart();
        $cart->load(['items.product.images', 'items.variant']);

        return view('livewire.cart.cart-drawer', [
            'cart' => $cart,
        ]);
    }
}
