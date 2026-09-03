<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @filamentStyles
        @livewireStyles
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center bg-paper px-4 py-12 font-sans text-ink-900 antialiased">
        <a href="{{ route('home') }}" wire:navigate aria-label="Dora Creations">
            <img src="{{ asset('black-logo.svg') }}" alt="Dora Creations" class="h-9 w-auto">
        </a>

        <div class="mt-8 w-full max-w-md rounded-2xl border border-ink-100 bg-cream/40 px-8 py-8 shadow-soft">
            {{ $slot }}
        </div>

        <a href="{{ route('home') }}" wire:navigate class="mt-6 text-xs font-semibold uppercase tracking-wide text-ink-400 hover:text-brand-500">
            ← Back to shop
        </a>

        @filamentScripts
        @livewireScripts
    </body>
</html>
