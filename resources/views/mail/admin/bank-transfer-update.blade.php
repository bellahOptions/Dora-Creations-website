<x-mail::message>
@if ($phase === \App\Notifications\BankTransferUpdate::PHASE_DECLARED)
# Customer has sent a bank transfer

{{ $order->customerName() }} says they have transferred `{{ $order->formattedTotal() }}` for order **{{ $order->order_number }}**.

Please check the bank account and confirm the payment in the admin panel once it lands.
@else
# New bank transfer order: {{ $order->order_number }}

{{ $order->customerName() }} chose to pay `{{ $order->formattedTotal() }}` by direct bank transfer. The stock is already held for this order.

We've emailed them the account details. Confirm the payment in the admin panel once the money arrives.
@endif

<x-mail::panel>
@foreach ($order->items as $item)
**{{ $item->quantity }}x** {{ $item->product_name }}{{ $item->variant_label ? " ({$item->variant_label})" : '' }}

@endforeach
</x-mail::panel>

Payment reference to look for: **{{ $order->order_number }}**

<x-mail::button :url="\App\Filament\Resources\OrderResource::getUrl('view', ['record' => $order])">
View in admin
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
