@props(['code', 'title', 'message'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }}, {{ $title }}, {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-paper px-6 py-12 text-center text-ink-900">
    <a href="{{ url('/') }}" aria-label="Dora Creations">
        <img src="{{ asset('black-logo.svg') }}" alt="Dora Creations" class="h-9 w-auto">
    </a>

    <p class="mt-12 font-display text-7xl uppercase text-ink-900 sm:text-8xl">{{ $code }}</p>
    <h1 class="mt-4 font-display text-2xl uppercase sm:text-3xl">{{ $title }}</h1>
    <p class="mt-4 max-w-md text-ink-500">{{ $message }}</p>

    <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
        <a href="{{ url('/') }}" class="inline-flex items-center rounded-full bg-ink-900 px-8 py-3 text-sm font-semibold uppercase tracking-wide text-paper transition hover:bg-ink-700">
            Back to shop
        </a>
        <a href="{{ url('/track-order') }}" class="inline-flex items-center rounded-full border border-ink-200 px-8 py-3 text-sm font-semibold uppercase tracking-wide text-ink-900 transition hover:bg-cream">
            Track an order
        </a>
    </div>
</body>
</html>
