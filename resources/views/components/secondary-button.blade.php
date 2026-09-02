<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-full border border-ink-300 bg-paper px-6 py-2.5 text-xs font-semibold uppercase tracking-widest text-ink-700 transition hover:border-ink-900 hover:text-ink-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
