<x-mail::message>
# Complete your bank transfer

Thanks {{ $order->shipping_full_name }}, your order **{{ $order->order_number }}** is reserved and waiting for your transfer of **{{ $order->formattedTotal() }}**.

<x-mail::panel>
**Bank:** {{ $settings->bank_name }}

**Account name:** {{ $settings->bank_account_name }}

**Account number:** {{ $settings->bank_account_number }}

**Amount:** {{ $order->formattedTotal() }}

**Transfer reference:** {{ $order->order_number }}
</x-mail::panel>

Please use **{{ $order->order_number }}** as the transfer narration/reference so we can match your payment quickly.

@if ($settings->bank_transfer_note)
{{ $settings->bank_transfer_note }}
@endif

Once you've sent it, let us know on your order page and we'll confirm as soon as it clears.

<x-mail::button :url="$trackingUrl">
View order &amp; confirm transfer
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
