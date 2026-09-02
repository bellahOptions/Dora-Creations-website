<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full bg-ink-900 px-6 py-2.5 text-xs font-semibold uppercase tracking-widest text-paper transition hover:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
