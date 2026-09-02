<?php

namespace App\Livewire\Cart;

use Livewire\Attributes\On;
use Livewire\Component;

class CartIndicator extends Component
{
    public int $itemCount = 0;

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // Wired up to the real Cart model in a later pass.
        $this->itemCount = 0;
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
