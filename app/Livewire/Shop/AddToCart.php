<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Livewire\Component;

class AddToCart extends Component
{
    public Product $product;

    public ?string $size = null;

    public ?string $color = null;

    public int $quantity = 1;

    public bool $justAdded = false;

    public function mount(Product $product): void
    {
        $this->product = $product->load('variants');

        if ($product->has_variants) {
            $firstAvailable = $product->variants->firstWhere('stock_quantity', '>', 0) ?? $product->variants->first();
            $this->size = $firstAvailable?->size;
            $this->color = $firstAvailable?->color;
        }
    }

    public function availableSizes(): array
    {
        return $this->product->variants->pluck('size')->filter()->unique()->values()->all();
    }

    public function availableColors(): array
    {
        return $this->product->variants->pluck('color')->filter()->unique()->values()->all();
    }

    public function selectSize(string $size): void
    {
        $this->size = $size;
        $this->justAdded = false;
    }

    public function selectColor(string $color): void
    {
        $this->color = $color;
        $this->justAdded = false;
    }

    public function getSelectedVariantProperty(): ?ProductVariant
    {
        if (! $this->product->has_variants) {
            return null;
        }

        return $this->product->variants->first(
            fn (ProductVariant $variant) => $variant->size === $this->size && $variant->color === $this->color
        );
    }

    public function getInStockProperty(): bool
    {
        if ($this->product->has_variants) {
            return (bool) $this->selectedVariant?->isInStock();
        }

        return $this->product->isInStock();
    }

    public function increment(): void
    {
        $this->quantity++;
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addToCart(CartService $cartService): void
    {
        $this->resetErrorBag();

        if ($this->product->has_variants && ! $this->selectedVariant) {
            $this->addError('variant', 'Please select a size/color.');

            return;
        }

        if (! $this->inStock) {
            $this->addError('stock', 'This item is currently out of stock.');

            return;
        }

        $cartService->addItem($this->product, $this->selectedVariant, $this->quantity);

        $this->justAdded = true;
        $this->quantity = 1;

        $this->dispatch('cart-updated');
        $this->dispatch('open-cart-drawer');
    }

    public function render()
    {
        return view('livewire.shop.add-to-cart');
    }
}
