<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dora Creations' }} — {{ config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? 'Nigerian-made fashion — tees, tote bags and more, designed and produced by Dora Creations.' }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @filamentStyles
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body class="flex min-h-screen flex-col bg-paper text-ink-900">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded focus:bg-ink-900 focus:px-4 focus:py-2 focus:text-paper">
        Skip to content
    </a>

    <div class="overflow-hidden bg-ink-900 py-2 text-paper">
        <div class="flex animate-marquee whitespace-nowrap text-xs font-semibold uppercase tracking-[0.2em]">
            @for ($i = 0; $i < 2; $i++)
                <span class="mx-6">Handmade in Nigeria</span>
                <span class="mx-6 text-brand-400">&#9670;</span>
                <span class="mx-6">Free naija-wide delivery on orders over &#8358;50,000</span>
                <span class="mx-6 text-brand-400">&#9670;</span>
                <span class="mx-6">Pay with Paystack or Flutterwave</span>
                <span class="mx-6 text-brand-400">&#9670;</span>
            @endfor
        </div>
    </div>

    <header x-data="{ mobileOpen: false }" class="sticky top-0 z-50 border-b border-ink-100 bg-paper/90 backdrop-blur">
        <div class="container-store flex h-20 items-center justify-between">
            <a href="{{ route('home') }}" class="font-display text-xl tracking-tight text-ink-900 sm:text-2xl">
                DORA <span class="text-brand-500">CREATIONS</span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-semibold uppercase tracking-wide lg:flex">
                <a href="{{ route('shop.index') }}" class="transition hover:text-brand-500 {{ request()->routeIs('shop.*') ? 'text-brand-500' : '' }}">Shop</a>
                <a href="{{ route('categories.index') }}" class="transition hover:text-brand-500 {{ request()->routeIs('categories.*') ? 'text-brand-500' : '' }}">Collections</a>
                <a href="{{ route('pages.show', 'about') }}" class="transition hover:text-brand-500">About</a>
                <a href="{{ route('pages.show', 'design-and-printing') }}" class="transition hover:text-brand-500">Design &amp; Print</a>
                <a href="{{ route('pages.show', 'contact') }}" class="transition hover:text-brand-500">Contact</a>
            </nav>

            <div class="flex items-center gap-4">
                <div class="hidden sm:block">
                    @livewire('currency-switcher')
                </div>

                @auth
                    <a href="{{ route('dashboard') }}" class="hidden text-ink-700 transition hover:text-brand-500 sm:block" aria-label="Account">
                        <x-heroicon-o-user class="h-6 w-6" />
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden text-ink-700 transition hover:text-brand-500 sm:block" aria-label="Login">
                        <x-heroicon-o-user class="h-6 w-6" />
                    </a>
                @endauth

                @livewire('cart.cart-indicator')

                <button @click="mobileOpen = !mobileOpen" class="text-ink-700 lg:hidden" aria-label="Toggle menu">
                    <x-heroicon-o-bars-3 class="h-7 w-7" x-show="!mobileOpen" />
                    <x-heroicon-o-x-mark class="h-7 w-7" x-show="mobileOpen" x-cloak />
                </button>
            </div>
        </div>

        <div x-show="mobileOpen" x-collapse x-cloak class="border-t border-ink-100 bg-paper lg:hidden">
            <nav class="container-store flex flex-col gap-1 py-4 text-sm font-semibold uppercase tracking-wide">
                <a href="{{ route('shop.index') }}" class="rounded px-2 py-2 hover:bg-ink-50">Shop</a>
                <a href="{{ route('categories.index') }}" class="rounded px-2 py-2 hover:bg-ink-50">Collections</a>
                <a href="{{ route('pages.show', 'about') }}" class="rounded px-2 py-2 hover:bg-ink-50">About</a>
                <a href="{{ route('pages.show', 'design-and-printing') }}" class="rounded px-2 py-2 hover:bg-ink-50">Design &amp; Print</a>
                <a href="{{ route('pages.show', 'contact') }}" class="rounded px-2 py-2 hover:bg-ink-50">Contact</a>
                <div class="mt-2 flex items-center justify-between px-2">
                    @livewire('currency-switcher')
                    @auth
                        <a href="{{ route('dashboard') }}" class="normal-case tracking-normal text-brand-600">My account</a>
                    @else
                        <a href="{{ route('login') }}" class="normal-case tracking-normal text-brand-600">Login</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-ink-100 bg-ink-900 text-paper">
        <div class="container-store grid gap-10 py-16 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="font-display text-lg">DORA <span class="text-brand-400">CREATIONS</span></p>
                <p class="mt-4 max-w-xs text-sm text-ink-200">
                    Nigerian-made fashion — tees, tote bags and more — designed and produced by
                    Dora herself, with a creative design &amp; printing studio behind the scenes.
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-300">Shop</p>
                <ul class="mt-4 space-y-2 text-sm text-ink-200">
                    <li><a href="{{ route('shop.index') }}" class="hover:text-brand-400">All products</a></li>
                    <li><a href="{{ route('categories.index') }}" class="hover:text-brand-400">Collections</a></li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-brand-400">Cart</a></li>
                    <li><a href="{{ route('order-tracking.lookup') }}" class="hover:text-brand-400">Track an order</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-300">The brand</p>
                <ul class="mt-4 space-y-2 text-sm text-ink-200">
                    <li><a href="{{ route('pages.show', 'about') }}" class="hover:text-brand-400">About Dora</a></li>
                    <li><a href="{{ route('pages.show', 'design-and-printing') }}" class="hover:text-brand-400">Design &amp; Printing</a></li>
                    <li><a href="{{ route('pages.show', 'shipping-and-returns') }}" class="hover:text-brand-400">Shipping &amp; returns</a></li>
                    <li><a href="{{ route('pages.show', 'contact') }}" class="hover:text-brand-400">Contact</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-300">Secure payments</p>
                <p class="mt-4 text-sm text-ink-200">We accept Paystack and Flutterwave — choose whichever works best for you at checkout.</p>
            </div>
        </div>

        <div class="border-t border-ink-800 py-6 text-center text-xs text-ink-400">
            &copy; {{ now()->year }} Dora Creations. All rights reserved.
        </div>
    </footer>

    @livewire('cart.cart-drawer')

    @filamentScripts
    @livewireScripts
    {{ $scripts ?? '' }}
</body>
</html>
