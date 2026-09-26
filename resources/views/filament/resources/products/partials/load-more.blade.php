@php
    use App\Filament\Resources\ProductResource\Pages\ListProducts;

    $records = $this->getTableRecords();
    $perPage = $this->getTableRecordsPerPage();

    $hasMore = method_exists($records, 'hasMorePages') && $records->hasMorePages();
    $next = is_numeric($perPage) ? (int) $perPage + ListProducts::CHUNK_SIZE : null;
    $total = method_exists($records, 'total') ? $records->total() : $records->count();

    // The running count is only worth showing once the catalogue is bigger
    // than a single chunk — on a handful of products it's just noise.
    $showCount = $total > ListProducts::CHUNK_SIZE;
@endphp

@if ($hasMore && $next)
    {{-- Scrolling replaces the page links. Filament still renders its own
         pager underneath, so hide it rather than leave two competing ways to
         move through the same list. --}}
    <style>
        .fi-resource-products .fi-ta-pagination {
            display: none;
        }
    </style>

    {{-- wire:key includes the current chunk so the sentinel is a fresh node
         after every load, which re-arms the observer. --}}
    <div
        wire:key="products-load-more-{{ $perPage }}"
        x-data
        x-init="
            const sentinel = new IntersectionObserver((entries) => {
                if (! entries[0].isIntersecting) {
                    return;
                }

                sentinel.disconnect();
                $wire.set('tableRecordsPerPage', {{ $next }});
            }, { rootMargin: '400px 0px' });

            sentinel.observe($el);
        "
        class="flex flex-col items-center gap-3 pt-2"
    >
        {{-- The observer does this automatically; the button is what happens
             when it doesn't (no JS, a keyboard user, a short viewport). --}}
        <x-filament::button
            type="button"
            color="gray"
            wire:click="$set('tableRecordsPerPage', {{ $next }})"
            wire:loading.attr="disabled"
            wire:target="$set"
        >
            Load more products
        </x-filament::button>

        @if ($showCount)
            <x-filament::badge color="gray">
                Showing {{ $records->count() }} of {{ $total }}
            </x-filament::badge>
        @endif
    </div>
@elseif ($showCount)
    <div class="flex justify-center pt-2">
        <x-filament::badge color="gray">
            Showing {{ $records->count() }} of {{ $total }}
        </x-filament::badge>
    </div>
@endif
