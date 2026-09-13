<x-layouts.storefront title="Collections">
    <section class="container-store py-16">
        <h1 class="font-display text-3xl uppercase sm:text-4xl">Collections</h1>
        <p class="mt-3 max-w-lg text-ink-500">Browse the Dora Creations range by collection.</p>

        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}"
                    x-data x-intersect.once="$el.classList.add('reveal-visible')"
                    class="reveal group relative flex aspect-[16/9] items-center gap-4 overflow-hidden rounded-2xl bg-ink-900 px-6 transition duration-300 hover:bg-ink-800">
                    <x-category-icon :category="$category" class="h-9 w-9 shrink-0 text-paper/80 transition duration-300 group-hover:scale-110" />
                    <div class="text-paper">
                        <p class="font-display text-xl uppercase">{{ $category->name }}</p>
                        <p class="mt-1 text-xs uppercase tracking-wide text-paper/70">{{ $category->products_count }} {{ Str::plural('piece', $category->products_count) }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.storefront>
