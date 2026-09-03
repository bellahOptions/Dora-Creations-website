<x-filament-panels::page>
    @php($topProducts = $this->getTopWishlistedProducts())

    @if ($topProducts->isNotEmpty())
        <x-filament::section heading="Most wishlisted products">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($topProducts as $product)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <p class="truncate text-sm font-semibold">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500">{{ $product->category?->name }}</p>
                        <p class="mt-2 text-xl font-bold">{{ $product->wishlisted_by_count }}</p>
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ \Illuminate\Support\Str::plural('save', $product->wishlisted_by_count) }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
