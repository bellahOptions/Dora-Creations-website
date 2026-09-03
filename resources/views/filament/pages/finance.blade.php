<x-filament-panels::page>
    @php($stats = $this->getRevenueStats())

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ([
            'Total revenue' => $stats['total'],
            'Refunded' => $stats['refunded'],
            'Net revenue' => $stats['net'],
            'Paystack' => $stats['paystack'],
            'Flutterwave' => $stats['flutterwave'],
        ] as $label => $kobo)
            <x-filament::section>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold">₦{{ number_format($kobo / 100, 2) }}</p>
            </x-filament::section>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @livewire(\App\Filament\Widgets\RevenueChart::class)
        </div>
        <div>
            @livewire(\App\Filament\Widgets\GatewaySplitChart::class)
        </div>
    </div>

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
