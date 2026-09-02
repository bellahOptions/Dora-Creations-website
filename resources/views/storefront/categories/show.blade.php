<x-layouts.storefront :title="$category->name" :description="$category->description">
    <section class="border-b border-ink-100 bg-paper-soft py-14">
        <div class="container-store">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Collection</p>
            <h1 class="mt-2 font-display text-3xl uppercase sm:text-4xl">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="mt-3 max-w-lg text-ink-500">{{ $category->description }}</p>
            @endif
        </div>
    </section>

    @livewire('shop.product-browser', ['categorySlug' => $category->slug], key('browser-'.$category->slug))
</x-layouts.storefront>
