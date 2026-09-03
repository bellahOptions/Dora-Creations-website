<x-mail::message>
# Thank you for your order!

We've received your payment; order `{{ $order->order_number }}` is now being prepared.

<x-mail::panel>
**Subtotal:** `{{ $order->formattedSubtotal() }}`

@if ($order->discount_kobo > 0)
**Discount{{ $order->discount_code ? " ({$order->discount_code})" : '' }}:** `-{{ $order->formattedDiscount() }}`

@endif
**Shipping:** `{{ $order->formattedShipping() }}`

**Total:** `{{ $order->formattedTotal() }}`
</x-mail::panel>

@if ($order->hasPreorderItems())
This order includes pre-order items, which will ship once available rather than right away.

@endif
<x-mail::button :url="route('order-tracking.show', $order->public_token)">
Track your order
</x-mail::button>

We'll let you know as soon as it ships.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
