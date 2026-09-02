<x-layouts.storefront title="Home">
    <section class="container-store flex min-h-[70vh] flex-col items-center justify-center py-24 text-center">
        <p x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal font-display text-4xl uppercase tracking-tight sm:text-6xl">
            Wear the <span class="text-brand-500">craft</span>.
        </p>
        <p x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal mt-6 max-w-xl text-ink-500" style="transition-delay: 120ms">
            Tees, tote bags and more — designed and made by hand by Dora Creations. Homepage coming together next.
        </p>
        <a href="{{ route('shop.index') }}" class="mt-8 inline-flex items-center rounded-full bg-ink-900 px-8 py-3 text-sm font-semibold uppercase tracking-wide text-paper transition hover:bg-brand-500">
            Shop the collection
        </a>
    </section>
</x-layouts.storefront>
