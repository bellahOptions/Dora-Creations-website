<div x-data="{ open: @entangle('open') }" x-show="open" x-cloak class="fixed inset-0 z-[60]" style="display: none;">
    <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-ink-900/50"></div>

    <aside x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-paper shadow-soft">

        <div class="flex items-center justify-between border-b border-ink-100 px-6 py-5">
            <h2 class="font-display text-lg uppercase">Your Cart</h2>
            <button wire:click="close" type="button" class="text-ink-500 hover:text-ink-900" aria-label="Close cart">
                <x-heroicon-o-x-mark class="h-6 w-6" />
            </button>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center px-6 text-center text-ink-400">
            <x-heroicon-o-shopping-bag class="h-10 w-10" />
            <p class="mt-4">Your cart is empty.</p>
            <a href="{{ route('shop.index') }}" wire:click="close" class="mt-4 text-sm font-semibold uppercase tracking-wide text-brand-500 hover:underline">
                Start shopping
            </a>
        </div>
    </aside>
</div>
