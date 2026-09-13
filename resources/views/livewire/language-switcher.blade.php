<div x-data="{ open: false }" x-on:locale-changed.window="window.location.reload()" class="relative">
    <button @click="open = !open" @click.outside="open = false" type="button"
        class="flex items-center gap-1 text-sm font-semibold text-ink-700 transition hover:text-brand-500"
        aria-label="{{ __('Select language') }}">
        <x-heroicon-o-language class="h-5 w-5" />
        <span class="uppercase">{{ $currentLocale }}</span>
        <x-heroicon-o-chevron-down class="h-4 w-4" />
    </button>

    <div x-show="open" x-transition x-cloak
        class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-lg border border-ink-100 bg-paper py-1 shadow-soft">
        @foreach ($locales as $code => $locale)
            @if ($code === 'en')
                <div class="my-1 border-t border-ink-100"></div>
            @endif
            <button wire:click="selectLocale('{{ $code }}')" @click="open = false" type="button"
                class="flex w-full items-center justify-between px-4 py-2 text-left text-sm hover:bg-ink-50 {{ $currentLocale === $code ? 'font-semibold text-brand-600' : 'text-ink-700' }}">
                <span>{{ $locale['native'] }}</span>
                <span class="text-xs uppercase text-ink-400">{{ $code }}</span>
            </button>
        @endforeach
    </div>
</div>
