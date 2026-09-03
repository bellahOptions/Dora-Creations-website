<x-layouts.account title="My Account">
    <p class="text-ink-500">Welcome back, {{ auth()->user()->name }}.</p>

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('account.orders.index') }}" class="rounded-xl border border-ink-100 p-5 transition hover:border-brand-500">
            <p class="font-display text-sm uppercase">Orders</p>
            <p class="mt-1 text-sm text-ink-500">Track, view receipts, share updates</p>
        </a>
        <a href="{{ route('account.wishlist.index') }}" class="rounded-xl border border-ink-100 p-5 transition hover:border-brand-500">
            <p class="font-display text-sm uppercase">Wishlist</p>
            <p class="mt-1 text-sm text-ink-500">Everything you've saved for later</p>
        </a>
        <a href="{{ route('account.addresses.index') }}" class="rounded-xl border border-ink-100 p-5 transition hover:border-brand-500">
            <p class="font-display text-sm uppercase">Addresses</p>
            <p class="mt-1 text-sm text-ink-500">Manage delivery addresses</p>
        </a>
        <a href="{{ route('account.settings') }}" class="rounded-xl border border-ink-100 p-5 transition hover:border-brand-500">
            <p class="font-display text-sm uppercase">Settings</p>
            <p class="mt-1 text-sm text-ink-500">Password, profile &amp; account</p>
        </a>
    </div>
</x-layouts.account>
