<x-mail::message>
# New order: {{ $order->order_number }}

{{ $order->customerName() }} just placed an order for `{{ $order->formattedTotal() }}`.

<x-mail::panel>
@foreach ($order->items as $item)
**{{ $item->quantity }}x** {{ $item->product_name }}{{ $item->variant_label ? " ({$item->variant_label})" : '' }}

@endforeach
</x-mail::panel>

<x-mail::button :url="\App\Filament\Resources\OrderResource::getUrl('view', ['record' => $order])">
View in admin
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
