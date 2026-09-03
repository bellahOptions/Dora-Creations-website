@props(['modal'])

<div
    x-data="{
        open: false,
        storageKey: 'dora-ad-dismissed-{{ $modal->uuid }}',
        init() {
            const alwaysShow = {{ $modal->frequency === \App\Models\AdModal::FREQUENCY_EVERY_VISIT ? 'true' : 'false' }};
            if (! alwaysShow) {
                try {
                    if (sessionStorage.getItem(this.storageKey)) return;
                } catch (e) {}
            }
            setTimeout(() => { this.open = true }, {{ max(0, (int) $modal->delay_seconds) * 1000 }});
        },
        dismiss() {
            this.open = false;
            try { sessionStorage.setItem(this.storageKey, '1'); } catch (e) {}
        },
    }"
    x-show="open"
    x-cloak
    @keydown.escape.window="dismiss()"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="ad-modal-title"
>
    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" @click="dismiss()" class="absolute inset-0 bg-ink-900/70 backdrop-blur-sm"></div>

    <div x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-md overflow-hidden rounded-2xl bg-paper shadow-2xl"
    >
        <button @click="dismiss()" type="button" aria-label="Close"
            class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-paper/90 text-ink-700 shadow-soft transition hover:bg-paper">
            <x-heroicon-o-x-mark class="h-5 w-5" />
        </button>

        @if ($modal->imageUrl())
            <div class="aspect-[16/9] w-full overflow-hidden bg-ink-100">
                <img src="{{ $modal->imageUrl() }}" alt="{{ $modal->title }}" class="h-full w-full object-cover">
            </div>
        @endif

        <div class="p-6 text-center sm:p-8">
            <h2 id="ad-modal-title" class="font-display text-2xl uppercase text-ink-900">{{ $modal->title }}</h2>

            @if ($modal->body)
                <div class="prose prose-sm mt-3 max-w-none text-ink-600">
                    {!! $modal->body !!}
                </div>
            @endif

            @if ($modal->cta_label && $modal->cta_url)
                <a href="{{ $modal->cta_url }}"
                    class="mt-6 inline-flex items-center justify-center rounded-full bg-ink-900 px-8 py-3 text-sm font-semibold uppercase tracking-wide text-paper transition hover:bg-brand-500">
                    {{ $modal->cta_label }}
                </a>
            @endif
        </div>
    </div>
</div>
