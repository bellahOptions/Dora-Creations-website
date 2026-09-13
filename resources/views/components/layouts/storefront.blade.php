<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $seoTitle = ($title ?? 'Dora Creations').', '.config('app.name');
        $seoDescription = $description ?? 'Nigerian-made fashion, tees, tote bags and more, designed and produced by Dora Creations.';
        $seoImage = $image ?? asset('logo-on-light-background.svg');
        $seoCanonical = $canonical ?? url()->current();
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:type" content="{{ $type ?? 'website' }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset('black-logo.svg'),
        ], JSON_HEX_TAG) !!}
    </script>
    {!! $schema ?? '' !!}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @filamentStyles
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body class="flex min-h-screen flex-col bg-paper text-ink-900">

    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:rounded focus:bg-ink-900 focus:px-4 focus:py-2 focus:text-paper">
        {{ __('Skip to content') }}
    </a>

    <div class="overflow-hidden bg-ink-900 py-2 text-paper">
        <div class="flex animate-marquee whitespace-nowrap text-xs font-semibold uppercase tracking-[0.2em]">
            @for ($i = 0; $i < 2; $i++)
                <span class="mx-6">{{ __('Premium Tees') }}</span>
                <span class="mx-6 text-brand-400">&#9670;</span>
                <span class="mx-6">{{ __('Free naija-wide delivery on orders over :amount', ['amount' => '₦50,000']) }}</span>
                <span class="mx-6 text-brand-400">&#9670;</span>
                <span class="mx-6">{{ __('Enjoy a smooth delivery process') }}</span>
                <span class="mx-6 text-brand-400">&#9670;</span>
            @endfor
        </div>
    </div>

    <header x-data="{ mobileOpen: false }" class="sticky top-0 z-50 border-b border-ink-100 bg-paper/90 backdrop-blur">
        <div class="container-store flex h-20 items-center justify-between">
            <a href="{{ route('home') }}" aria-label="Dora Creations">
                <img src="{{ asset('black-logo.svg') }}" alt="Dora Creations" class="h-8 w-auto sm:h-9">
            </a>

            <nav class="hidden items-center gap-8 text-sm font-semibold uppercase tracking-wide lg:flex">
                <a href="{{ route('shop.index') }}" class="transition hover:text-brand-500 {{ request()->routeIs('shop.*') ? 'text-brand-500' : '' }}">{{ __('Shop') }}</a>
                <a href="{{ route('categories.index') }}" class="transition hover:text-brand-500 {{ request()->routeIs('categories.*') ? 'text-brand-500' : '' }}">{{ __('Collections') }}</a>
                <a href="{{ route('pages.show', 'about') }}" class="transition hover:text-brand-500">{{ __('About') }}</a>
                <a href="{{ route('pages.show', 'design-and-printing') }}" class="transition hover:text-brand-500">{{ __('Design & Print') }}</a>
                <a href="{{ route('pages.show', 'contact') }}" class="transition hover:text-brand-500">{{ __('Contact') }}</a>
            </nav>

            <div class="flex items-center gap-4">
                <div class="hidden items-center gap-4 sm:flex">
                    @livewire('language-switcher')
                    @livewire('currency-switcher')
                </div>

                @auth
                    <a href="{{ route('account.wishlist.index') }}" wire:navigate class="hidden text-ink-700 transition hover:text-brand-500 sm:block" aria-label="{{ __('My wishlist') }}">
                        <x-heroicon-o-heart class="h-6 w-6" />
                    </a>

                    <div class="hidden sm:block">
                        <x-dropdown align="right" width="52">
                            <x-slot name="trigger">
                                <button class="text-ink-700 transition hover:text-brand-500" aria-label="{{ __('Account menu') }}">
                                    @if (auth()->user()->avatarUrl())
                                        <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-7 w-7 rounded-full object-cover">
                                    @else
                                        <x-heroicon-o-user class="h-6 w-6" />
                                    @endif
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="border-b border-ink-100 px-4 py-2 text-xs text-ink-400">
                                    {{ __('Signed in as') }} <span class="font-semibold text-ink-700">{{ auth()->user()->name }}</span>
                                </div>
                                <x-dropdown-link href="{{ route('dashboard') }}" wire:navigate>{{ __('Account overview') }}</x-dropdown-link>
                                <x-dropdown-link href="{{ route('account.orders.index') }}" wire:navigate>{{ __('My orders') }}</x-dropdown-link>
                                <x-dropdown-link href="{{ route('account.wishlist.index') }}" wire:navigate>{{ __('Wishlist') }}</x-dropdown-link>
                                <x-dropdown-link href="{{ route('account.addresses.index') }}" wire:navigate>{{ __('Addresses') }}</x-dropdown-link>
                                <x-dropdown-link href="{{ route('account.settings') }}" wire:navigate>{{ __('Settings') }}</x-dropdown-link>
                                @if (auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                                    <x-dropdown-link href="{{ route('filament.admin.pages.dashboard') }}">{{ __('Admin dashboard') }}</x-dropdown-link>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$el.closest('form').submit();">
                                        {{ __('Log out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="hidden text-ink-700 transition hover:text-brand-500 sm:block" aria-label="{{ __('Login') }}">
                        <x-heroicon-o-user class="h-6 w-6" />
                    </a>
                @endauth

                @livewire('cart.cart-indicator')

                <button @click="mobileOpen = !mobileOpen" class="text-ink-700 lg:hidden" aria-label="{{ __('Toggle menu') }}">
                    <x-heroicon-o-bars-3 class="h-7 w-7" x-show="!mobileOpen" />
                    <x-heroicon-o-x-mark class="h-7 w-7" x-show="mobileOpen" x-cloak />
                </button>
            </div>
        </div>

        <div x-show="mobileOpen" x-collapse x-cloak class="border-t border-ink-100 bg-paper lg:hidden">
            <nav class="container-store flex flex-col gap-1 py-4 text-sm font-semibold uppercase tracking-wide">
                <a href="{{ route('shop.index') }}" class="rounded px-2 py-2 hover:bg-ink-50">{{ __('Shop') }}</a>
                <a href="{{ route('categories.index') }}" class="rounded px-2 py-2 hover:bg-ink-50">{{ __('Collections') }}</a>
                <a href="{{ route('pages.show', 'about') }}" class="rounded px-2 py-2 hover:bg-ink-50">{{ __('About') }}</a>
                <a href="{{ route('pages.show', 'design-and-printing') }}" class="rounded px-2 py-2 hover:bg-ink-50">{{ __('Design & Print') }}</a>
                <a href="{{ route('pages.show', 'contact') }}" class="rounded px-2 py-2 hover:bg-ink-50">{{ __('Contact') }}</a>
                <div class="mt-2 flex items-center justify-between px-2">
                    @livewire('language-switcher')
                    @livewire('currency-switcher')
                    @auth
                        <a href="{{ route('dashboard') }}" class="normal-case tracking-normal text-brand-600">{{ __('My account') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="normal-case tracking-normal text-brand-600">{{ __('Login') }}</a>
                    @endauth
                </div>
                @auth
                    @if (auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                        <a href="{{ route('filament.admin.pages.dashboard') }}" class="mt-2 rounded px-2 py-2 hover:bg-ink-50">{{ __('Admin dashboard') }}</a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-ink-100 bg-ink-900 text-paper">
        <div class="container-store grid gap-10 py-16 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <img src="{{ asset('logo-on-dark-background.svg') }}" alt="Dora Creations" class="h-8 w-auto">
                <p class="mt-4 max-w-xs text-sm text-ink-200">
                    {{ __('Nigerian-made fashion, tees, tote bags and more, designed and produced by Dora herself, with a creative design & printing studio behind the scenes.') }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-300">{{ __('Shop') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-ink-200">
                    <li><a href="{{ route('shop.index') }}" class="hover:text-brand-400">{{ __('All products') }}</a></li>
                    <li><a href="{{ route('categories.index') }}" class="hover:text-brand-400">{{ __('Collections') }}</a></li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-brand-400">{{ __('Cart') }}</a></li>
                    <li><a href="{{ route('order-tracking.lookup') }}" class="hover:text-brand-400">{{ __('Track an order') }}</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-300">{{ __('The brand') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-ink-200">
                    <li><a href="{{ route('pages.show', 'about') }}" class="hover:text-brand-400">{{ __('About Dora') }}</a></li>
                    <li><a href="{{ route('pages.show', 'design-and-printing') }}" class="hover:text-brand-400">{{ __('Design & Printing') }}</a></li>
                    <li><a href="{{ route('pages.show', 'shipping-and-returns') }}" class="hover:text-brand-400">{{ __('Shipping & returns') }}</a></li>
                    <li><a href="{{ route('pages.show', 'contact') }}" class="hover:text-brand-400">{{ __('Contact') }}</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-300">{{ __('Secure payments') }}</p>
                <p class="mt-4 text-sm text-ink-200">{{ __('We accept Paystack and Flutterwave; choose whichever works best for you at checkout.') }}</p>
            </div>
        </div>

        <div class="border-t border-ink-800 py-6 text-center text-xs text-ink-400">
            &copy; {{ now()->year }} Dora Creations. {{ __('All rights reserved.') }}
        </div>
    </footer>

    @livewire('cart.cart-drawer')

    @php($activeAdModal = \App\Models\AdModal::active()->latest()->first())
    @if ($activeAdModal)
        <x-ad-modal :modal="$activeAdModal" />
    @endif

    @filamentScripts
    @livewireScripts
    {{ $scripts ?? '' }}
</body>
</html>
