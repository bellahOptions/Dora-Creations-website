<x-layouts.storefront title="Collections">
    <section class="container-store py-16">
        <h1 class="font-display text-3xl uppercase sm:text-4xl">Collections</h1>
        <p class="mt-3 max-w-lg text-ink-500">Browse the Dora Creations range by collection.</p>

        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}"
                    x-data x-intersect.once="$el.classList.add('reveal-visible')"
                    class="reveal group relative aspect-[16/9] overflow-hidden rounded-2xl bg-ink-100">
                    <img src="{{ $category->imageUrl() ?? asset('placeholder.svg') }}" alt="{{ $category->name }}"
                        onerror="this.onerror=null;this.src='{{ asset('placeholder.svg') }}';"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink-900/70 via-transparent to-transparent"></div>
                    <div class="absolute bottom-5 left-5 text-paper">
                        <p class="font-display text-xl uppercase">{{ $category->name }}</p>
                        <p class="mt-1 text-xs uppercase tracking-wide text-paper/70">{{ $category->products_count }} {{ Str::plural('piece', $category->products_count) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.storefront>
