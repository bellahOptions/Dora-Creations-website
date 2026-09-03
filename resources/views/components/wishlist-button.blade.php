@props(['product', 'compact' => false])

@php
    $isWishlisted = auth()->check() && auth()->user()->hasWishlisted($product);
@endphp

<button
    type="button"
    x-data="{ wishlisted: {{ $isWishlisted ? 'true' : 'false' }}, loading: false }"
    @click.stop.prevent="
        if (loading) return;
        loading = true;
        fetch('{{ route('wishlist.toggle', $product) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        }).then((response) => {
            if (response.status === 401) {
                window.location = '{{ route('login') }}';
                return null;
            }
            return response.json();
        }).then((data) => {
            if (data) {
                wishlisted = data.wishlisted;
                window.dispatchEvent(new CustomEvent('wishlist-updated'));
            }
            loading = false;
        }).catch(() => { loading = false; });
    "
    :disabled="loading"
    :aria-label="wishlisted ? 'Remove from wishlist' : 'Add to wishlist'"
    @class([
        'flex items-center justify-center rounded-full transition disabled:opacity-50',
        'h-9 w-9 bg-paper/90 shadow-soft hover:bg-paper' => $compact,
        'h-11 w-11 border border-ink-200 hover:border-brand-500' => ! $compact,
    ])
>
    <x-heroicon-o-heart class="h-5 w-5 text-ink-700" x-show="!wishlisted" />
    <x-heroicon-s-heart class="h-5 w-5 text-brand-500" x-show="wishlisted" x-cloak />
</button>
