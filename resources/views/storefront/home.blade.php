<x-layouts.storefront title="Home">
    {{-- Hero slideshow --}}
    <section class="relative">
        <div x-data="{ swiper: null }" x-init="swiper = new Swiper($refs.hero, {
                modules: [SwiperModules.EffectCreative, SwiperModules.Pagination, SwiperModules.Autoplay],
                effect: 'creative',
                creativeEffect: {
                    prev: { shadow: true, translate: ['-20%', 0, -1] },
                    next: { translate: ['100%', 0, 0] },
                },
                loop: true,
                autoplay: { delay: 5500, disableOnInteraction: false },
                pagination: { el: $refs.heroPagination, clickable: true },
            })">
            <div x-ref="hero" class="swiper h-[80vh] min-h-[520px] w-full overflow-hidden bg-ink-900">
                <div class="swiper-wrapper">
                    @forelse ($slides as $slide)
                        <div class="swiper-slide relative">
                            <img src="{{ $slide->url() }}" alt="{{ $slide->headline }}" class="absolute inset-0 h-full w-full object-cover opacity-60">
                            <div class="relative flex h-full items-center">
                                <div class="container-store">
                                    <p class="animate-fade-up font-display text-4xl uppercase leading-[0.95] text-paper sm:text-6xl lg:text-7xl">
                                        {{ $slide->headline }}
                                    </p>
                                    @if ($slide->subheadline)
                                        <p class="animate-fade-up mt-5 max-w-md text-paper/80" style="animation-delay: 150ms">
                                            {{ $slide->subheadline }}
                                        </p>
                                    @endif
                                    @if ($slide->cta_label)
                                        <a href="{{ $slide->cta_url ?? route('shop.index') }}"
                                            class="animate-fade-up mt-8 inline-flex items-center rounded-full bg-paper px-8 py-3 text-sm font-semibold uppercase tracking-wide text-ink-900 transition hover:bg-brand-500 hover:text-paper"
                                            style="animation-delay: 300ms">
                                            {{ $slide->cta_label }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="swiper-slide flex h-full items-center justify-center">
                            <p class="font-display text-3xl uppercase text-paper">Dora Creations</p>
                        </div>
                    @endforelse
                </div>
            </div>
            <div x-ref="heroPagination" class="absolute inset-x-0 bottom-6 z-10 flex justify-center gap-2 [&_.swiper-pagination-bullet]:h-2 [&_.swiper-pagination-bullet]:w-2 [&_.swiper-pagination-bullet]:rounded-full [&_.swiper-pagination-bullet]:bg-paper/50 [&_.swiper-pagination-bullet-active]:bg-paper"></div>
        </div>
    </section>

    {{-- Category tiles --}}
    <section class="container-store py-20">
        <div class="mb-10 flex items-end justify-between">
            <h2 class="font-display text-2xl uppercase sm:text-3xl">Shop by collection</h2>
            <a href="{{ route('categories.index') }}" class="text-sm font-semibold uppercase tracking-wide text-brand-600 hover:underline">View all</a>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category->slug) }}"
                    x-data x-intersect.once="$el.classList.add('reveal-visible')"
                    class="reveal group relative aspect-square overflow-hidden rounded-2xl bg-ink-100"
                    style="transition-delay: {{ $loop->index * 80 }}ms">
                    @if ($category->imageUrl())
                        <img src="{{ $category->imageUrl() }}" alt="{{ $category->name }}"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-ink-900/70 via-transparent to-transparent"></div>
                    <p class="absolute bottom-4 left-4 font-display text-sm uppercase tracking-wide text-paper sm:text-base">{{ $category->name }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Featured products --}}
    @if ($featuredProducts->isNotEmpty())
        <section class="bg-paper-soft py-20">
            <div class="container-store">
                <div class="mb-10 flex items-end justify-between">
                    <h2 class="font-display text-2xl uppercase sm:text-3xl">Featured pieces</h2>
                    <a href="{{ route('shop.index') }}" class="text-sm font-semibold uppercase tracking-wide text-brand-600 hover:underline">Shop all</a>
                </div>

                <div class="grid grid-cols-2 gap-x-5 gap-y-10 sm:grid-cols-4">
                    @foreach ($featuredProducts as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Brand story teaser — subtle nod to design & printing --}}
    <section class="container-store grid grid-cols-1 gap-10 py-20 sm:grid-cols-2 sm:items-center">
        <div x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal aspect-[4/3] overflow-hidden rounded-2xl bg-ink-900">
            <div class="flex h-full items-center justify-center font-display text-2xl uppercase text-paper/70">Dora at work</div>
        </div>
        <div x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal" style="transition-delay: 120ms">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">The studio</p>
            <h2 class="mt-3 font-display text-2xl uppercase sm:text-3xl">Made by hand, piece by piece</h2>
            <p class="mt-4 max-w-md text-ink-500">
                Every tee, tote and hoodie is designed and produced by Dora herself. Behind the fashion line
                sits a full creative design &amp; printing studio — the same craft, put to work for brands
                and individuals across Nigeria.
            </p>
            <a href="{{ route('pages.show', 'design-and-printing') }}" class="mt-6 inline-flex items-center text-sm font-semibold uppercase tracking-wide text-ink-900 hover:text-brand-600">
                Explore the design &amp; printing studio →
            </a>
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="border-t border-ink-100 bg-ink-900 py-12 text-paper">
        <div class="container-store grid grid-cols-1 gap-8 text-center sm:grid-cols-3">
            <div x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal">
                <p class="font-display text-lg uppercase">Handmade in Nigeria</p>
                <p class="mt-2 text-sm text-ink-300">Every piece cut and finished by the Dora Creations studio.</p>
            </div>
            <div x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal" style="transition-delay: 100ms">
                <p class="font-display text-lg uppercase">Secure checkout</p>
                <p class="mt-2 text-sm text-ink-300">Pay your way with Paystack or Flutterwave.</p>
            </div>
            <div x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal" style="transition-delay: 200ms">
                <p class="font-display text-lg uppercase">No account needed</p>
                <p class="mt-2 text-sm text-ink-300">Check out as a guest, or sign in to track every order.</p>
            </div>
        </div>
    </section>
</x-layouts.storefront>
