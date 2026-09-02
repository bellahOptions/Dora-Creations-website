<x-layouts.storefront :title="$product->name" :description="$product->meta_description ?? Str::limit(strip_tags($product->description), 150)">
    <div class="container-store py-10">
        <nav class="mb-8 text-xs uppercase tracking-wide text-ink-400">
            <a href="{{ route('shop.index') }}" class="hover:text-brand-500">Shop</a>
            @if ($product->category)
                <span class="mx-2">/</span>
                <a href="{{ route('categories.show', $product->category->slug) }}" class="hover:text-brand-500">{{ $product->category->name }}</a>
            @endif
            <span class="mx-2">/</span>
            <span class="text-ink-600">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
            <div x-data="{ swiper: null }" x-init="swiper = new Swiper($refs.gallery, {
                    modules: [SwiperModules.EffectFade, SwiperModules.Pagination, SwiperModules.Navigation],
                    loop: {{ $product->images->count() > 1 ? 'true' : 'false' }},
                    effect: 'fade',
                    fadeEffect: { crossFade: true },
                    pagination: { el: $refs.pagination, clickable: true },
                    navigation: { nextEl: $refs.next, prevEl: $refs.prev },
                })" class="relative">
                <div x-ref="gallery" class="swiper aspect-[4/5] overflow-hidden rounded-2xl bg-ink-100">
                    <div class="swiper-wrapper">
                        @forelse ($product->images as $image)
                            <div class="swiper-slide">
                                <img src="{{ $image->url() }}" alt="{{ $image->alt_text ?? $product->name }}" class="h-full w-full object-cover">
                            </div>
                        @empty
                            <div class="swiper-slide flex items-center justify-center text-ink-300">No image</div>
                        @endforelse
                    </div>
                </div>

                @if ($product->images->count() > 1)
                    <div x-ref="pagination" class="mt-4 flex justify-center gap-2 [&_.swiper-pagination-bullet]:h-2 [&_.swiper-pagination-bullet]:w-2 [&_.swiper-pagination-bullet]:rounded-full [&_.swiper-pagination-bullet]:bg-ink-300 [&_.swiper-pagination-bullet-active]:bg-ink-900"></div>
                    <button x-ref="prev" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-paper/80 p-2 shadow-soft" aria-label="Previous image">
                        <x-heroicon-o-chevron-left class="h-5 w-5" />
                    </button>
                    <button x-ref="next" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-paper/80 p-2 shadow-soft" aria-label="Next image">
                        <x-heroicon-o-chevron-right class="h-5 w-5" />
                    </button>
                @endif
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $product->category?->name }}</p>
                <h1 class="mt-2 font-display text-3xl uppercase sm:text-4xl">{{ $product->name }}</h1>

                <div class="mt-4 flex items-center gap-3">
                    <span class="text-xl font-semibold">{{ $product->formattedPrice() }}</span>
                    @if ($product->isOnSale())
                        <span class="text-sm text-ink-400 line-through">{{ $product->formattedCompareAtPrice() }}</span>
                    @endif
                </div>

                @if ($product->approvedReviews()->count() > 0)
                    <div class="mt-3 flex items-center gap-2 text-sm text-ink-500">
                        <span class="text-gold">{{ str_repeat('★', (int) round($product->averageRating())) }}{{ str_repeat('☆', 5 - (int) round($product->averageRating())) }}</span>
                        <span>{{ $product->averageRating() }} ({{ $product->approvedReviews()->count() }} reviews)</span>
                    </div>
                @endif

                <div class="prose prose-sm mt-6 max-w-none text-ink-600">
                    {!! nl2br(e($product->description)) !!}
                </div>

                @livewire('shop.add-to-cart', ['product' => $product], key('add-to-cart-'.$product->id))

                <div class="mt-8 grid grid-cols-2 gap-4 border-t border-ink-100 pt-6 text-xs text-ink-500">
                    <div>
                        <p class="font-semibold text-ink-700">Handmade quality</p>
                        <p class="mt-1">Cut, printed and finished by hand in the Dora Creations studio.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-ink-700">Naija-wide delivery</p>
                        <p class="mt-1">Pay with Paystack or Flutterwave — track your order after checkout.</p>
                    </div>
                </div>
            </div>
        </div>

        @if ($product->approvedReviews->isNotEmpty())
            <section class="mt-20 max-w-2xl">
                <h2 class="font-display text-xl uppercase">Reviews</h2>
                <div class="mt-6 space-y-6">
                    @foreach ($product->approvedReviews as $review)
                        <div class="border-b border-ink-100 pb-6">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold">{{ $review->user?->name ?? 'Verified buyer' }}</p>
                                <span class="text-gold text-sm">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                            </div>
                            @if ($review->title)
                                <p class="mt-1 text-sm font-semibold text-ink-800">{{ $review->title }}</p>
                            @endif
                            <p class="mt-1 text-sm text-ink-500">{{ $review->body }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($related->isNotEmpty())
            <section class="mt-20">
                <h2 class="font-display text-xl uppercase">You may also like</h2>
                <div class="mt-6 grid grid-cols-2 gap-x-5 gap-y-10 sm:grid-cols-4">
                    @foreach ($related as $relatedProduct)
                        <x-product-card :product="$relatedProduct" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.storefront>
