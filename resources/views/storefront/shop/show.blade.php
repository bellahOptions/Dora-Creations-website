@php
    $galleryItems = collect();
    if ($product->featured_image_path) {
        $galleryItems->push(['url' => $product->featuredImageUrl(), 'alt' => $product->name]);
    }
    foreach ($product->images as $image) {
        $galleryItems->push(['url' => $image->url(), 'alt' => $image->alt_text ?? $product->name]);
    }

    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->short_description ?? Str::limit(strip_tags($product->description), 300),
        'sku' => $product->sku,
        'image' => $galleryItems->pluck('url')->values()->all(),
        'offers' => [
            '@type' => 'Offer',
            'url' => route('shop.show', $product),
            'priceCurrency' => 'NGN',
            'price' => number_format($product->price_kobo / 100, 2, '.', ''),
            'availability' => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
    ];
    if ($product->approvedReviews->isNotEmpty()) {
        $productSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $product->averageRating(),
            'reviewCount' => $product->approvedReviews->count(),
        ];
    }
@endphp

<x-layouts.storefront :title="$product->name"
    :description="$product->meta_description ?? $product->short_description ?? Str::limit(strip_tags($product->description), 150)"
    :image="$product->featuredImageUrl()"
    type="product"
    :schema="'<script type=\'application/ld+json\'>'.json_encode($productSchema, JSON_HEX_TAG).'</script>'">
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
                    loop: {{ $galleryItems->count() > 1 ? 'true' : 'false' }},
                    effect: 'fade',
                    fadeEffect: { crossFade: true },
                    pagination: { el: $refs.pagination, clickable: true },
                    navigation: { nextEl: $refs.next, prevEl: $refs.prev },
                })" class="relative">
                <div x-ref="gallery" class="swiper aspect-[4/5] overflow-hidden rounded-2xl bg-ink-100">
                    <div class="swiper-wrapper">
                        @forelse ($galleryItems as $image)
                            <div class="swiper-slide">
                                <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}"
                                    onerror="this.onerror=null;this.src='{{ asset('placeholder.svg') }}';"
                                    class="h-full w-full object-cover">
                            </div>
                        @empty
                            <div class="swiper-slide">
                                <img src="{{ asset('placeholder.svg') }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                            </div>
                        @endforelse
                    </div>
                </div>

                @if ($galleryItems->count() > 1)
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
                <div class="flex items-center gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $product->category?->name }}</p>
                    @if ($product->is_preorder)
                        <span class="rounded-full bg-ink-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-paper">Pre-order</span>
                    @endif
                </div>
                <h1 class="mt-2 font-display text-3xl uppercase sm:text-4xl">{{ $product->name }}</h1>

                <div class="mt-4 flex items-center gap-3">
                    <span class="text-xl font-semibold">{{ $product->displayPrice() }}</span>
                    @if ($product->isOnSale())
                        <span class="text-sm text-ink-400 line-through">{{ $product->displayCompareAtPrice() }}</span>
                    @endif
                </div>

                @if ($product->approvedReviews()->count() > 0)
                    <div class="mt-3 flex items-center gap-2 text-sm text-ink-500">
                        <span class="text-gold">{{ str_repeat('★', (int) round($product->averageRating())) }}{{ str_repeat('☆', 5 - (int) round($product->averageRating())) }}</span>
                        <span>{{ $product->averageRating() }} ({{ $product->approvedReviews()->count() }} reviews)</span>
                    </div>
                @endif

                @if ($product->short_description)
                    <p class="mt-6 text-base font-medium text-ink-800">{{ $product->short_description }}</p>
                @endif

                <div class="prose prose-sm mt-4 max-w-none text-ink-600">
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
                        <p class="mt-1">Pay with Paystack or Flutterwave; track your order after checkout.</p>
                    </div>
                </div>
            </div>
        </div>

        <section class="mt-20 max-w-2xl">
            <h2 class="font-display text-xl uppercase">Reviews</h2>

            @if ($product->approvedReviews->isNotEmpty())
                <div class="mt-6 space-y-6">
                    @foreach ($product->approvedReviews as $review)
                        <div class="border-b border-ink-100 pb-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold">{{ $review->displayName() }}</p>
                                    @if ($review->is_verified_purchase)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-forest-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-forest-700">
                                            <x-heroicon-o-check-badge class="h-3.5 w-3.5" /> Verified purchase
                                        </span>
                                    @endif
                                </div>
                                <span class="text-gold text-sm">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                            </div>
                            @if ($review->title)
                                <p class="mt-1 text-sm font-semibold text-ink-800">{{ $review->title }}</p>
                            @endif
                            @if ($review->body)
                                <p class="mt-1 text-sm text-ink-500">{{ $review->body }}</p>
                            @endif
                            @if ($review->screenshotUrl())
                                <img src="{{ $review->screenshotUrl() }}" alt="Review screenshot"
                                    onerror="this.onerror=null;this.src='{{ asset('placeholder.svg') }}';"
                                    class="mt-3 max-w-xs rounded-xl border border-ink-100">
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-4 text-sm text-ink-500">No reviews yet, be the first to share your thoughts.</p>
            @endif

            <div class="mt-8">
                @auth
                    @livewire('product.review-form', ['product' => $product], key('review-form-'.$product->id))
                @else
                    <p class="text-sm text-ink-500">
                        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Sign in</a>
                        to leave a review if you've purchased this item.
                    </p>
                @endauth
            </div>
        </section>

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
