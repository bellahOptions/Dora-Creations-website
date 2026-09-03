<x-mail::message>
@switch($order->status)
@case(\App\Models\Order::STATUS_DELIVERY_ONGOING)
# Your order is on its way!

Order `{{ $order->order_number }}` has left the studio and is on its way to you.
@break

@case(\App\Models\Order::STATUS_DELIVERED)
# Delivered!

Order `{{ $order->order_number }}` has been delivered. We hope you love it.
@break

@case(\App\Models\Order::STATUS_REJECTED_REFUNDED)
# Order rejected/refunded

Order `{{ $order->order_number }}` was rejected/refunded. Reply to this email if you have questions.
@break

@case(\App\Models\Order::STATUS_PAYMENT_FAILED)
# We couldn't confirm your payment

We weren't able to confirm payment for order `{{ $order->order_number }}`, so it wasn't placed. If you were charged, reply to this email and we'll sort it out; otherwise, feel free to try again.
@break

@default
# Update on your order

Order `{{ $order->order_number }}` is now {{ strtolower($order->statusLabel()) }}.
@endswitch

<x-mail::button :url="route('order-tracking.show', $order->public_token)">
Track your order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
