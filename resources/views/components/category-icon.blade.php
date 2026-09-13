@props(['category'])

@php
    $slug = $category->slug ?? '';
@endphp

@if (str_contains($slug, 'hoodie') || str_contains($slug, 'sweat'))
    <svg {{ $attributes }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M8 4.5 A4 4 0 0 1 16 4.5 L20 9 L17 12 L17 21 L7 21 L7 12 L4 9 Z" />
        <path d="M8.5 16 L15.5 16" />
    </svg>
@elseif (str_contains($slug, 'tee') || str_contains($slug, 'shirt'))
    <svg {{ $attributes }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M2 7 L8 3 C8.8 5 10.2 6 12 6 C13.8 6 15.2 5 16 3 L22 7 L18 11 L18 21 L6 21 L6 11 Z" />
    </svg>
@elseif (str_contains($slug, 'tote') || str_contains($slug, 'bag'))
    <x-heroicon-o-shopping-bag {{ $attributes }} />
@elseif (str_contains($slug, 'accessor'))
    <svg {{ $attributes }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="6.5" cy="13" r="3.5" />
        <circle cx="17.5" cy="13" r="3.5" />
        <line x1="10" y1="12" x2="14" y2="12" />
        <line x1="3" y1="10" x2="1" y2="7" />
        <line x1="21" y1="10" x2="23" y2="7" />
    </svg>
@else
    <x-heroicon-o-tag {{ $attributes }} />
@endif
