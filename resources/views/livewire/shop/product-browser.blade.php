<div class="container-store py-12">
    <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            @unless ($category)
                <h1 class="font-display text-3xl uppercase sm:text-4xl">Shop</h1>
            @endunless
            <p class="mt-2 text-sm text-ink-500">{{ $products->total() }} {{ Str::plural('piece', $products->total()) }}</p>
        </div>

        <div class="flex items-center gap-3">
            <label for="sort" class="text-xs font-semibold uppercase tracking-wide text-ink-400">Sort</label>
            <select id="sort" wire:model.live="sort" class="rounded-full border-ink-200 text-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="newest">Newest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="name">Name A–Z</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-10 lg:grid-cols-[240px_1fr]">
        <aside class="space-y-8">
            <div>
                <input type="text" wire:model.live.debounce.400ms="q" placeholder="Search products…"
                    class="w-full rounded-full border-ink-200 text-sm placeholder:text-ink-300 focus:border-brand-500 focus:ring-brand-500">
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Collection</p>
                <div class="mt-3 space-y-2 text-sm">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="radio" wire:model.live="category" value="" class="text-brand-500 focus:ring-brand-500">
                        All
                    </label>
                    @foreach ($categories as $cat)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="category" value="{{ $cat->slug }}" class="text-brand-500 focus:ring-brand-500">
                            {{ $cat->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Size</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($sizes as $sizeOption)
                        <button type="button" wire:click="$set('size', '{{ $size === $sizeOption ? '' : $sizeOption }}')"
                            class="h-9 w-9 rounded-full border text-xs font-semibold transition {{ $size === $sizeOption ? 'border-ink-900 bg-ink-900 text-paper' : 'border-ink-200 text-ink-600 hover:border-ink-900' }}">
                            {{ $sizeOption }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Color</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($colors as $colorOption)
                        <button type="button" wire:click="$set('color', '{{ $color === $colorOption ? '' : $colorOption }}')"
                            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $color === $colorOption ? 'border-ink-900 bg-ink-900 text-paper' : 'border-ink-200 text-ink-600 hover:border-ink-900' }}">
                            {{ $colorOption }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Price</p>
                <div class="mt-3 space-y-2 text-sm">
                    @foreach (['' => 'All', 'under-10k' => 'Under ₦10,000', '10k-20k' => '₦10,000 – ₦20,000', '20k-30k' => '₦20,000 – ₦30,000', 'over-30k' => 'Over ₦30,000'] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" wire:model.live="price" value="{{ $value }}" class="text-brand-500 focus:ring-brand-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <button wire:click="resetFilters" type="button" class="text-xs font-semibold uppercase tracking-wide text-brand-600 hover:underline">
                Reset filters
            </button>
        </aside>

        <div wire:loading.class="opacity-50" class="transition">
            @if ($products->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ink-200 py-24 text-center">
                    <p class="font-display text-xl uppercase">No products found</p>
                    <p class="mt-2 text-sm text-ink-500">Try adjusting or resetting your filters.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-5 gap-y-10 sm:grid-cols-3">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div class="mt-12">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
