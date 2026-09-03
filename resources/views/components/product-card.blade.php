@props(['product'])

<a href="{{ route('shop.show', $product) }}"
    x-data x-intersect.once="$el.classList.add('reveal-visible')"
    class="reveal group block">
    <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-ink-100">
        <img src="{{ $product->featuredImageUrl() ?? asset('placeholder.svg') }}"
            alt="{{ $product->images->first()->alt_text ?? $product->name }}"
            loading="lazy"
            onerror="this.onerror=null;this.src='{{ asset('placeholder.svg') }}';"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">

        @if ($product->isOnSale())
            <span class="absolute left-3 top-3 rounded-full bg-brand-500 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-paper">
                Sale
            </span>
        @endif

        @if (! $product->isInStock())
            <span class="absolute inset-0 flex items-center justify-center bg-ink-900/60 text-xs font-semibold uppercase tracking-wide text-paper">
                Sold out
            </span>
        @endif
    </div>

    <div class="mt-3">
        <p class="text-xs uppercase tracking-wide text-ink-400">{{ $product->category?->name }}</p>
        <h3 class="mt-1 text-sm font-semibold text-ink-900 transition group-hover:text-brand-600">{{ $product->name }}</h3>
        <div class="mt-1 flex items-center gap-2">
            <span class="text-sm font-semibold">{{ $product->displayPrice() }}</span>
            @if ($product->isOnSale())
                <span class="text-xs text-ink-400 line-through">{{ $product->displayCompareAtPrice() }}</span>
            @endif
        </div>
    </div>
</a>
