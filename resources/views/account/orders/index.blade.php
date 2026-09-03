<x-layouts.account title="My Orders">
    @if ($orders->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ink-200 py-20 text-center">
            <p class="text-ink-500">You haven't placed any orders yet.</p>
            <a href="{{ route('shop.index') }}" class="mt-4 rounded-full bg-ink-900 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-paper hover:bg-brand-500">
                Start shopping
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($orders as $order)
                <a href="{{ route('order-tracking.show', $order->public_token) }}"
                    class="flex flex-col gap-3 rounded-xl border border-ink-100 p-5 transition hover:border-brand-500 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold">{{ $order->order_number }}</p>
                        <p class="mt-1 text-sm text-ink-500">
                            {{ $order->created_at->format('d M Y') }} · {{ $order->items->sum('quantity') }} {{ Str::plural('item', $order->items->sum('quantity')) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide
                            {{ match(true) {
                                $order->status === \App\Models\Order::STATUS_DELIVERED => 'bg-forest-100 text-forest-700',
                                in_array($order->status, [\App\Models\Order::STATUS_REJECTED_REFUNDED, \App\Models\Order::STATUS_PAYMENT_FAILED]) => 'bg-red-100 text-red-700',
                                $order->status === \App\Models\Order::STATUS_PENDING_PAYMENT => 'bg-ink-100 text-ink-500',
                                default => 'bg-gold/20 text-ink-700',
                            } }}">
                            {{ $order->statusLabel() }}
                        </span>
                        <span class="font-semibold">{{ $order->formattedTotal() }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    @endif
</x-layouts.account>
