<div class="container-store py-14">
    <h1 class="font-display text-3xl uppercase sm:text-4xl">Your Cart</h1>

    @if ($cart->items->isEmpty())
        <div class="mt-12 flex flex-col items-center justify-center rounded-2xl border border-dashed border-ink-200 py-24 text-center">
            <x-heroicon-o-shopping-bag class="h-10 w-10 text-ink-300" />
            <p class="mt-4 text-ink-500">Your cart is empty.</p>
            <a href="{{ route('shop.index') }}" class="mt-4 rounded-full bg-ink-900 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-paper hover:bg-brand-500">
                Start shopping
            </a>
        </div>
    @else
        <div class="mt-10 grid grid-cols-1 gap-12 lg:grid-cols-[1fr_360px]">
            <div wire:loading.class="opacity-50" class="transition">
                <ul class="divide-y divide-ink-100 border-y border-ink-100">
                    @foreach ($cart->items as $item)
                        <li wire:key="cart-page-item-{{ $item->id }}" class="flex gap-5 py-6">
                            <img src="{{ $item->product->images->first()?->url() }}" alt="{{ $item->product->name }}"
                                class="h-28 w-24 flex-shrink-0 rounded-xl object-cover">

                            <div class="flex flex-1 flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold">{{ $item->product->name }}</p>
                                    @if ($item->variant)
                                        <p class="mt-1 text-sm text-ink-400">{{ $item->variant->label() }}</p>
                                    @endif
                                    <p class="mt-1 text-sm text-ink-500">{{ app(\App\Services\CurrencyService::class)->format($item->unit_price_kobo) }} each</p>

                                    <button wire:click="removeItem({{ $item->id }})" class="mt-2 text-xs font-semibold uppercase tracking-wide text-ink-400 hover:text-brand-500">
                                        Remove
                                    </button>
                                </div>

                                <div class="mt-4 flex items-center justify-between sm:mt-0 sm:flex-col sm:items-end sm:gap-3">
                                    <div class="flex items-center gap-3 rounded-full border border-ink-200 px-3 py-1.5">
                                        <button wire:click="decrementItem({{ $item->id }})" class="text-ink-500 hover:text-ink-900" aria-label="Decrease quantity">−</button>
                                        <span class="w-6 text-center text-sm font-semibold">{{ $item->quantity }}</span>
                                        <button wire:click="incrementItem({{ $item->id }})" class="text-ink-500 hover:text-ink-900" aria-label="Increase quantity">+</button>
                                    </div>
                                    <span class="font-semibold">{{ $item->displayLineTotal() }}</span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('shop.index') }}" class="mt-6 inline-flex items-center text-sm font-semibold uppercase tracking-wide text-ink-700 hover:text-brand-600">
                    ← Continue shopping
                </a>
            </div>

            <div class="h-fit rounded-2xl border border-ink-100 bg-paper-soft p-6">
                <h2 class="font-display text-lg uppercase">Order summary</h2>

                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Subtotal</dt>
                        <dd class="font-semibold">{{ $cart->displaySubtotal() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Shipping</dt>
                        <dd class="font-semibold">{{ $shippingKobo === 0 ? 'Free' : app(\App\Services\CurrencyService::class)->format($shippingKobo) }}</dd>
                    </div>
                    @if ($freeShippingThreshold && $cart->subtotalKobo() < $freeShippingThreshold)
                        <p class="text-xs text-ink-400">
                            Add {{ app(\App\Services\CurrencyService::class)->format($freeShippingThreshold - $cart->subtotalKobo()) }} more for free shipping.
                        </p>
                    @endif
                </dl>

                <div class="mt-4 flex justify-between border-t border-ink-200 pt-4 text-base font-semibold">
                    <span>Total</span>
                    <span>{{ app(\App\Services\CurrencyService::class)->format($cart->subtotalKobo() + $shippingKobo) }}</span>
                </div>
                <p class="mt-2 text-xs text-ink-400">You'll be charged in Naira (₦) at checkout.</p>

                <a href="{{ route('checkout.index') }}" class="mt-6 block rounded-full bg-ink-900 px-6 py-3 text-center text-sm font-semibold uppercase tracking-wide text-paper transition hover:bg-brand-500">
                    Proceed to checkout
                </a>
                <p class="mt-3 text-center text-xs text-ink-400">No account needed — checkout as a guest or sign in.</p>
            </div>
        </div>
    @endif
</div>
