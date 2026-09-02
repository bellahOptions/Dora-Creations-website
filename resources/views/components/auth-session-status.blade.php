@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-forest-50 px-4 py-3 text-sm font-medium text-forest-700']) }}>
        {{ $status }}
    </div>
@endif
