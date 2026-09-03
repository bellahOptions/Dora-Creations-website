@php
    $steps = [
        \App\Models\Order::STATUS_PROCESSING => 'Processing',
        \App\Models\Order::STATUS_DELIVERY_ONGOING => 'Delivery ongoing',
        \App\Models\Order::STATUS_DELIVERED => 'Delivered',
    ];
    $stepKeys = array_keys($steps);
    $currentIndex = array_search($order->status, $stepKeys, true);
    $isRejected = $order->status === \App\Models\Order::STATUS_REJECTED_REFUNDED;
@endphp

<x-layouts.storefront title="Order {{ $order->order_number }}">
    @if (session('order-confirmed'))
        <section class="border-b border-ink-100 bg-ink-900 py-10 text-center text-paper">
            <div class="container-store">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-paper text-ink-900">
                    <x-heroicon-o-check class="h-7 w-7" />
                </div>
                <h1 class="mt-4 font-display text-2xl uppercase sm:text-3xl">Order confirmed</h1>
                <p class="mt-2 text-paper/75">
                    Thank you, your payment went through and order <span class="font-semibold text-paper">{{ $order->order_number }}</span> is on its way to being prepared.
                    A confirmation has been sent to {{ $order->customerEmail() }}.
                </p>
            </div>
        </section>
    @endif

    <section class="container-store py-14">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Order {{ $order->order_number }}</p>
                <h1 class="mt-1 font-display text-2xl uppercase sm:text-3xl">{{ $order->statusLabel() }}</h1>
                @if ($order->hasPreorderItems())
                    <p class="mt-2 text-sm text-ink-500">This order includes pre-order items; they'll ship once available.</p>
                @endif
            </div>

            <div x-data="{
                copied: false,
                share() {
                    const url = window.location.href;
                    if (navigator.share) {
                        navigator.share({ title: 'Order {{ $order->order_number }}', url });
                    } else {
                        navigator.clipboard.writeText(url);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    }
                }
            }" class="flex items-center gap-3">
                <button @click="share" type="button" class="rounded-full border border-ink-300 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-ink-700 transition hover:border-ink-900">
                    <span x-show="!copied">Share delivery update</span>
                    <span x-show="copied" x-cloak>Link copied ✓</span>
                </button>
                <button onclick="window.print()" type="button" class="rounded-full bg-ink-900 px-5 py-2 text-xs font-semibold uppercase tracking-wide text-paper transition hover:bg-brand-500">
                    Print receipt
                </button>
            </div>
        </div>

        @if ($isRejected)
            <div class="mt-8 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
                This order was rejected / refunded. Contact us if you have questions.
            </div>
        @elseif (! $order->isPaid())
            <div class="mt-8 rounded-xl border border-gold/40 bg-gold/10 p-5 text-sm text-ink-700">
                Awaiting payment confirmation.
            </div>
        @else
            <div class="mt-10 grid grid-cols-3 gap-2">
                @foreach ($steps as $key => $label)
                    @php $stepIndex = array_search($key, $stepKeys, true); @endphp
                    <div class="text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold {{ $stepIndex <= $currentIndex ? 'bg-ink-900 text-paper' : 'bg-ink-100 text-ink-400' }}">
                            {{ $stepIndex + 1 }}
                        </div>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide {{ $stepIndex <= $currentIndex ? 'text-ink-900' : 'text-ink-400' }}">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-14 grid grid-cols-1 gap-12 lg:grid-cols-[1fr_320px]">
            <div>
                <h2 class="font-display text-lg uppercase">Items</h2>
                <ul class="mt-4 divide-y divide-ink-100 border-y border-ink-100">
                    @foreach ($order->items as $item)
                        <li class="flex items-center gap-4 py-4">
                            <img src="{{ $item->product?->images->first()?->url() ?? asset('placeholder.svg') }}" alt="{{ $item->product_name }}"
                                onerror="this.onerror=null;this.src='{{ asset('placeholder.svg') }}';"
                                class="h-16 w-14 rounded-lg object-cover">
                            <div class="flex-1">
                                <p class="font-semibold">
                                    {{ $item->product_name }}
                                    @if ($item->is_preorder)
                                        <span class="ml-1 rounded-full bg-ink-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-paper">Pre-order</span>
                                    @endif
                                </p>
                                @if ($item->variant_label)
                                    <p class="text-sm text-ink-400">{{ $item->variant_label }}</p>
                                @endif
                                <p class="text-sm text-ink-400">Qty {{ $item->quantity }}</p>
                            </div>
                            <span class="font-semibold">{{ $item->formattedLineTotal() }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($order->statusHistories->isNotEmpty())
                    <h2 class="mt-10 font-display text-lg uppercase">Status history</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ($order->statusHistories as $history)
                            <li class="flex justify-between border-b border-ink-100 pb-3">
                                <span>{{ \Illuminate\Support\Str::headline($history->status) }}</span>
                                <span class="text-ink-400">{{ $history->created_at->format('d M Y, H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="h-fit space-y-6">
                <div class="rounded-2xl border border-ink-100 bg-paper-soft p-6">
                    <h2 class="font-display text-sm uppercase">Delivery address</h2>
                    <p class="mt-3 text-sm text-ink-600">{{ $order->shipping_full_name }}</p>
                    <p class="text-sm text-ink-500">{{ $order->shipping_phone }}</p>
                    <p class="text-sm text-ink-500">
                        {{ collect([$order->shipping_line1, $order->shipping_line2, $order->shipping_city, $order->shipping_state, $order->shipping_country])->filter()->implode(', ') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-ink-100 bg-paper-soft p-6">
                    <h2 class="font-display text-sm uppercase">Receipt</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-500">Subtotal</dt><dd class="font-mono">{{ \App\Support\Money::ngn($order->subtotal_kobo) }}</dd></div>
                        @if ($order->discount_kobo > 0)
                            <div class="flex justify-between"><dt class="text-ink-500">Discount {{ $order->discount_code ? "({$order->discount_code})" : '' }}</dt><dd class="font-mono">−{{ $order->formattedDiscount() }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-ink-500">Shipping</dt><dd class="font-mono">{{ $order->shipping_kobo === 0 ? 'Free' : \App\Support\Money::ngn($order->shipping_kobo) }}</dd></div>
                        <div class="flex justify-between border-t border-ink-200 pt-2 font-semibold"><dt>Total</dt><dd class="font-mono">{{ $order->formattedTotal() }}</dd></div>
                    </dl>
                    @if ($order->payment_gateway)
                        <p class="mt-3 text-xs text-ink-400">Paid via {{ \Illuminate\Support\Str::headline($order->payment_gateway) }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.storefront>
