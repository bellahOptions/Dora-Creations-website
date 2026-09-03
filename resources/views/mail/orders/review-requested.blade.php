@php
    $products = $order->items->pluck('product')->filter()->unique('id');
@endphp
<x-mail::message>
# How was it?

We'd love to hear what you think of your recent order, `{{ $order->order_number }}`.

@foreach ($products as $product)
<x-mail::button :url="route('shop.show', $product)">
Review {{ $product->name }}
</x-mail::button>

@endforeach
Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
