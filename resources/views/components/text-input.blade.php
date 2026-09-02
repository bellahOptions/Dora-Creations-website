@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-lg border-ink-200 text-ink-900 placeholder:text-ink-300 focus:border-brand-500 focus:ring-brand-500']) }}>
