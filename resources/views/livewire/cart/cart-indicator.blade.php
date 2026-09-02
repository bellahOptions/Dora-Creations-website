<button wire:click="openDrawer" type="button" class="relative text-ink-700 transition hover:text-brand-500" aria-label="Open cart">
    <x-heroicon-o-shopping-bag class="h-6 w-6" />
    @if ($itemCount > 0)
        <span class="absolute -right-2 -top-2 flex h-5 w-5 animate-pop-in items-center justify-center rounded-full bg-brand-500 text-[11px] font-bold text-paper">
            {{ $itemCount }}
        </span>
    @endif
</button>
