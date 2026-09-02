@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold uppercase tracking-wide text-ink-500']) }}>
    {{ $value ?? $slot }}
</label>
