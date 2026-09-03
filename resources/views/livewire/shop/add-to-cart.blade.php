<div class="mt-6 space-y-6">
    @if ($product->has_variants)
        @if (count($this->availableSizes()) > 0)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Size</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($this->availableSizes() as $sizeOption)
                        <button type="button" wire:click="selectSize('{{ $sizeOption }}')"
                            class="h-10 w-10 rounded-full border text-sm font-semibold transition {{ $size === $sizeOption ? 'border-ink-900 bg-ink-900 text-paper' : 'border-ink-200 text-ink-600 hover:border-ink-900' }}">
                            {{ $sizeOption }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if (count($this->availableColors()) > 0)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Color</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($this->availableColors() as $colorOption)
                        <button type="button" wire:click="selectColor('{{ $colorOption }}')"
                            class="rounded-full border px-4 py-1.5 text-sm font-semibold transition {{ $color === $colorOption ? 'border-ink-900 bg-ink-900 text-paper' : 'border-ink-200 text-ink-600 hover:border-ink-900' }}">
                            {{ $colorOption }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @error('variant')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    @endif

    @error('stock')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    @if ($product->is_preorder)
        <div class="flex items-center gap-2 rounded-lg bg-cream/40 px-4 py-3 text-sm text-ink-700">
            <x-heroicon-o-clock class="h-5 w-5 flex-shrink-0 text-ink-500" />
            <span><span class="font-semibold">Pre-order</span>, {{ $product->preorderLabel() }}</span>
        </div>
    @endif

    <div class="flex items-center gap-4">
        <div class="flex items-center gap-3 rounded-full border border-ink-200 px-3 py-2">
            <button wire:click="decrement" type="button" class="text-ink-500 hover:text-ink-900" aria-label="Decrease quantity">−</button>
            <span class="w-6 text-center text-sm font-semibold">{{ $quantity }}</span>
            <button wire:click="increment" type="button" class="text-ink-500 hover:text-ink-900" aria-label="Increase quantity">+</button>
        </div>

        @if ($this->canPurchase)
            <button wire:click="addToCart" wire:loading.attr="disabled"
                class="flex-1 rounded-full bg-ink-900 px-8 py-3 text-sm font-semibold uppercase tracking-wide text-paper transition hover:bg-brand-500 disabled:opacity-60">
                <span wire:loading.remove wire:target="addToCart">
                    @if ($justAdded)
                        Added ✓
                    @elseif ($product->is_preorder && ! $this->inStock)
                        Pre-order now
                    @else
                        Add to cart
                    @endif
                </span>
                <span wire:loading wire:target="addToCart">Adding…</span>
            </button>
        @else
            <button disabled class="flex-1 cursor-not-allowed rounded-full bg-ink-200 px-8 py-3 text-sm font-semibold uppercase tracking-wide text-ink-400">
                Sold out
            </button>
        @endif
    </div>
</div>
