<x-layouts.storefront title="Track your order">
    <section class="container-store max-w-md py-16">
        <h1 class="font-display text-3xl uppercase">Track your order</h1>
        <p class="mt-3 text-sm text-ink-500">Enter your order number and the email you checked out with.</p>

        <form method="POST" action="{{ route('order-tracking.find') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <x-input-label for="order_number" value="Order number" />
                <x-text-input id="order_number" name="order_number" type="text" class="mt-1" value="{{ old('order_number') }}" placeholder="DC-20260101-ABCDE" required />
                <x-input-error :messages="$errors->get('order_number')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1" value="{{ old('email') }}" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <x-primary-button class="w-full py-3">Track order</x-primary-button>
        </form>
    </section>
</x-layouts.storefront>
