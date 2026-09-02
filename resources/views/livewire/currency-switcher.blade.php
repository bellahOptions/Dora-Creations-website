<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" @click.outside="open = false" type="button"
        class="flex items-center gap-1 text-sm font-semibold text-ink-700 transition hover:text-brand-500">
        {{ $currencies[$currency] }} {{ $currency }}
        <x-heroicon-o-chevron-down class="h-4 w-4" />
    </button>

    <div x-show="open" x-transition x-cloak
        class="absolute right-0 z-50 mt-2 w-32 overflow-hidden rounded-lg border border-ink-100 bg-paper shadow-soft">
        @foreach ($currencies as $code => $symbol)
            <button wire:click="selectCurrency('{{ $code }}')" @click="open = false" type="button"
                class="flex w-full items-center justify-between px-4 py-2 text-left text-sm hover:bg-ink-50 {{ $currency === $code ? 'font-semibold text-brand-600' : 'text-ink-700' }}">
                <span>{{ $code }}</span>
                <span>{{ $symbol }}</span>
            </button>
        @endforeach
    </div>
</div>
