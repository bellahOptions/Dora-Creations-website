<x-layouts.storefront title="My Account">
    <section class="container-store py-16">
        <h1 class="font-display text-3xl uppercase">My Account</h1>
        <p class="mt-4 text-ink-500">Welcome back, {{ auth()->user()->name }}. Your account area is coming together here shortly.</p>
    </section>
</x-layouts.storefront>
