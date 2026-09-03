<x-layouts.account title="My Wishlist">
    @if ($products->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ink-200 py-20 text-center">
            <x-heroicon-o-heart class="h-10 w-10 text-ink-300" />
            <p class="mt-4 text-ink-500">You haven't saved anything yet.</p>
            <a href="{{ route('shop.index') }}" class="mt-4 rounded-full bg-ink-900 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-paper hover:bg-brand-500">
                Start browsing
            </a>
        </div>
    @else
        <div class="grid grid-cols-2 gap-x-5 gap-y-10 sm:grid-cols-3">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
</x-layouts.account>
