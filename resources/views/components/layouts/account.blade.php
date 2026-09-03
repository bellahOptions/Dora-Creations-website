@props(['title' => 'My Account'])

<x-layouts.storefront :title="$title">
    <div class="container-store py-12">
        <h1 class="font-display text-3xl uppercase sm:text-4xl">My Account</h1>

        <div class="mt-8 grid grid-cols-1 gap-10 lg:grid-cols-[220px_1fr]">
            <nav class="flex gap-2 overflow-x-auto text-sm font-semibold uppercase tracking-wide lg:flex-col lg:overflow-visible">
                <a href="{{ route('dashboard') }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request()->routeIs('dashboard') ? 'bg-ink-900 text-paper' : 'text-ink-600 hover:bg-ink-50' }}">Overview</a>
                <a href="{{ route('account.orders.index') }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request()->routeIs('account.orders.*') ? 'bg-ink-900 text-paper' : 'text-ink-600 hover:bg-ink-50' }}">Orders</a>
                <a href="{{ route('account.wishlist.index') }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request()->routeIs('account.wishlist.*') ? 'bg-ink-900 text-paper' : 'text-ink-600 hover:bg-ink-50' }}">Wishlist</a>
                <a href="{{ route('account.addresses.index') }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request()->routeIs('account.addresses.*') ? 'bg-ink-900 text-paper' : 'text-ink-600 hover:bg-ink-50' }}">Addresses</a>
                <a href="{{ route('account.settings') }}" class="whitespace-nowrap rounded-lg px-3 py-2 transition {{ request()->routeIs('account.settings') ? 'bg-ink-900 text-paper' : 'text-ink-600 hover:bg-ink-50' }}">Settings</a>
            </nav>

            <div>
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.storefront>
