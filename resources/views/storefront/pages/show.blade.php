<x-layouts.storefront :title="$page->title" :description="$page->meta_description">
    <section class="border-b border-ink-100 bg-paper-soft py-16">
        <div class="container-store max-w-3xl text-center">
            <p x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">
                Dora Creations
            </p>
            <h1 x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal mt-3 font-display text-3xl uppercase sm:text-5xl" style="transition-delay: 80ms">
                {{ $page->title }}
            </h1>
        </div>
    </section>

    <section class="container-store max-w-3xl py-16">
        <div x-data x-intersect.once="$el.classList.add('reveal-visible')"
            class="reveal prose prose-neutral max-w-none prose-headings:font-display prose-headings:uppercase prose-a:text-brand-600">
            {!! $page->content !!}
        </div>

        @if ($page->slug === 'contact')
            <div x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal mt-12 grid grid-cols-1 gap-6 rounded-2xl border border-ink-100 bg-paper-soft p-8 sm:grid-cols-2">
                @if ($settings->contact_email)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Email</p>
                        <a href="mailto:{{ $settings->contact_email }}" class="mt-1 block font-semibold text-ink-900 hover:text-brand-600">{{ $settings->contact_email }}</a>
                    </div>
                @endif
                @if ($settings->contact_phone)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Phone</p>
                        <a href="tel:{{ $settings->contact_phone }}" class="mt-1 block font-semibold text-ink-900 hover:text-brand-600">{{ $settings->contact_phone }}</a>
                    </div>
                @endif
                @if ($settings->social_instagram || $settings->social_twitter || $settings->social_facebook)
                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">Follow along</p>
                        <div class="mt-2 flex gap-4 text-sm font-semibold">
                            @if ($settings->social_instagram)
                                <a href="{{ $settings->social_instagram }}" target="_blank" rel="noopener" class="hover:text-brand-600">Instagram</a>
                            @endif
                            @if ($settings->social_twitter)
                                <a href="{{ $settings->social_twitter }}" target="_blank" rel="noopener" class="hover:text-brand-600">Twitter / X</a>
                            @endif
                            @if ($settings->social_facebook)
                                <a href="{{ $settings->social_facebook }}" target="_blank" rel="noopener" class="hover:text-brand-600">Facebook</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if ($page->slug === 'design-and-printing')
            <div x-data x-intersect.once="$el.classList.add('reveal-visible')" class="reveal mt-12 flex flex-col items-start gap-4 rounded-2xl border border-ink-100 bg-paper-soft p-8 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-ink-600">Have a design or print project in mind?</p>
                <a href="{{ route('pages.show', 'contact') }}" class="rounded-full bg-ink-900 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-paper transition hover:bg-brand-500">
                    Get in touch
                </a>
            </div>
        @endif

        @if (in_array($page->slug, ['about', 'shipping-and-returns']))
            <div class="mt-12 text-center">
                <a href="{{ route('shop.index') }}" class="inline-flex items-center text-sm font-semibold uppercase tracking-wide text-ink-900 hover:text-brand-600">
                    Shop the collection →
                </a>
            </div>
        @endif
    </section>
</x-layouts.storefront>
