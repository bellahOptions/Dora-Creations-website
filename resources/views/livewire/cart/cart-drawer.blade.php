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
            <h2 class="font-display text-lg uppercase">Your Cart ({{ $cart->itemCount() }})</h2>
            <button wire:click="close" type="button" class="text-ink-500 hover:text-ink-900" aria-label="Close cart">
                <x-heroicon-o-x-mark class="h-6 w-6" />
            </button>
        </div>

        @if ($cart->items->isEmpty())
            <div class="flex flex-1 flex-col items-center justify-center px-6 text-center text-ink-400">
                <x-heroicon-o-shopping-bag class="h-10 w-10" />
                <p class="mt-4">Your cart is empty.</p>
                <a href="{{ route('shop.index') }}" wire:click="close" class="mt-4 text-sm font-semibold uppercase tracking-wide text-brand-500 hover:underline">
                    Start shopping
                </a>
            </div>
        @else
            <div class="flex-1 overflow-y-auto px-6 py-4" wire:loading.class="opacity-50">
                <ul class="divide-y divide-ink-100">
                    @foreach ($cart->items as $item)
                        <li wire:key="cart-drawer-item-{{ $item->id }}" class="flex gap-4 py-4">
                            <img src="{{ $item->product->images->first()?->url() }}" alt="{{ $item->product->name }}"
                                class="h-20 w-16 flex-shrink-0 rounded object-cover">

                            <div class="flex flex-1 flex-col">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold">{{ $item->product->name }}</p>
                                        @if ($item->variant)
                                            <p class="text-xs text-ink-400">{{ $item->variant->label() }}</p>
                                        @endif
                                    </div>
                                    <button wire:click="removeItem({{ $item->id }})" class="text-ink-300 hover:text-brand-500" aria-label="Remove item">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2 rounded-full border border-ink-200 px-2 py-1 text-sm">
                                        <button wire:click="decrementItem({{ $item->id }})" class="px-1 text-ink-500 hover:text-ink-900" aria-label="Decrease quantity">−</button>
                                        <span class="w-5 text-center">{{ $item->quantity }}</span>
                                        <button wire:click="incrementItem({{ $item->id }})" class="px-1 text-ink-500 hover:text-ink-900" aria-label="Increase quantity">+</button>
                                    </div>
                                    <span class="text-sm font-semibold">{{ $item->displayLineTotal() }}</span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="border-t border-ink-100 px-6 py-5">
                <div class="flex items-center justify-between text-sm font-semibold uppercase tracking-wide">
                    <span>Subtotal</span>
                    <span>{{ $cart->displaySubtotal() }}</span>
                </div>
                <p class="mt-1 text-xs text-ink-400">Shipping calculated at checkout — you'll be charged in Naira (₦).</p>

                <a href="{{ route('cart.index') }}" wire:click="close"
                    class="mt-4 block rounded-full border border-ink-900 px-6 py-3 text-center text-sm font-semibold uppercase tracking-wide text-ink-900 transition hover:bg-ink-900 hover:text-paper">
                    View cart
                </a>
                <a href="{{ route('checkout.index') }}" wire:click="close"
                    class="mt-3 block rounded-full bg-ink-900 px-6 py-3 text-center text-sm font-semibold uppercase tracking-wide text-paper transition hover:bg-brand-500">
                    Checkout
                </a>
            </div>
        @endif
    </aside>
</div>
