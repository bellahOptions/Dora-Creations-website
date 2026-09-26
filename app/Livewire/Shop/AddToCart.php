<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
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

    /**
     * Colors that exist for the selected size (all colors when the
     * product has no sizes).
     */
    public function availableColors(): array
    {
        return $this->product->variants
            ->when($this->size, fn ($variants) => $variants->where('size', $this->size))
            ->pluck('color')->filter()->unique()->values()->all();
    }

    /**
     * A product flagged as variant-based but saved with no variant rows has no
     * options left to render. Without this the visitor only ever sees "please
     * select a size/color" with nothing to pick, so the product is treated as
     * unavailable instead of unsellable-but-blaming-the-customer.
     */
    public function getOptionsMissingProperty(): bool
    {
        return $this->product->has_variants && $this->product->variants->isEmpty();
    }

    /**
     * Human-readable summary of the current selection, e.g. "M / Black".
     */
    public function getSelectedOptionLabelProperty(): ?string
    {
        return $this->selectedVariant?->label() ?: null;
    }

    public function sizeInStock(string $size): bool
    {
        return $this->product->is_preorder
            || $this->product->variants->where('size', $size)->contains(fn (ProductVariant $v) => $v->isInStock());
    }

    public function colorInStock(string $color): bool
    {
        return $this->product->is_preorder
            || $this->product->variants
                ->when($this->size, fn ($variants) => $variants->where('size', $this->size))
                ->where('color', $color)
                ->contains(fn (ProductVariant $v) => $v->isInStock());
    }

    public function selectSize(string $size): void
    {
        $this->size = $size;
        $this->justAdded = false;

        // Keep the color valid for the new size.
        $colors = $this->availableColors();

        if ($colors && ! in_array($this->color, $colors, true)) {
            $this->color = collect($colors)->first(fn ($c) => $this->colorInStock($c)) ?? $colors[0];
        }
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
            // No options configured yet: there's no per-variant stock to check,
            // and we accept the order anyway so the studio can confirm the
            // choice afterwards (it just takes longer to deliver).
            if ($this->optionsMissing) {
                return true;
            }

            return (bool) $this->selectedVariant?->isInStock();
        }

        return $this->product->isInStock();
    }

    /**
     * Whether the current selection can be purchased — real stock, or the
     * whole product is open for pre-order (which bypasses per-variant
     * stock too, since the admin has explicitly opened it up for sale).
     */
    public function getCanPurchaseProperty(): bool
    {
        return $this->product->is_preorder || $this->inStock;
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

        // Only demand a choice when there is actually something to choose.
        // With no options configured the order goes through and the studio
        // confirms the size/colour afterwards.
        if ($this->product->has_variants && ! $this->optionsMissing && ! $this->selectedVariant) {
            $this->addError('variant', 'Please select a size/color.');

            return;
        }

        if (! $this->canPurchase) {
            $this->addError('stock', 'This item is currently out of stock.');

            return;
        }

        $cartService->addItem($this->product, $this->selectedVariant, $this->quantity);

        ActivityLogger::visitor(
            "Added \"{$this->product->name}\" to cart (qty {$this->quantity}).",
            $this->product,
        );

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
