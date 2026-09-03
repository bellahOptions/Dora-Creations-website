<x-mail::message>
# New review awaiting approval

{{ $review->displayName() }} left a {{ $review->rating }}-star review for "{{ $review->product?->name }}".

@if ($review->title)
**{{ $review->title }}**

@endif
@if ($review->body)
<x-mail::panel>
{{ $review->body }}
</x-mail::panel>
@endif

<x-mail::button :url="\App\Filament\Resources\ReviewResource::getUrl('edit', ['record' => $review])">
Review it
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
