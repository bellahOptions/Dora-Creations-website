<?php

namespace App\Livewire\Cart;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartIndicator extends Component
{
    public int $itemCount = 0;

    public function mount(CartService $cartService): void
    {
        $this->itemCount = $cartService->itemCount();
    }

    #[On('cart-updated')]
    public function refreshCount(CartService $cartService): void
    {
        $this->itemCount = $cartService->itemCount();
    }

    public function openDrawer(): void
    {
        $this->dispatch('open-cart-drawer');
    }

    public function render()
    {
        return view('livewire.cart.cart-indicator');
    }
}
