<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $palette ?? 'black' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                try {
                    const palette = localStorage.getItem('palette');

                    if (['black', 'chocolate', 'yellow'].includes(palette)) {
                        document.documentElement.dataset.theme = palette;
                    }
                } catch (e) {}

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @php
            $meta = $page['props']['meta'] ?? [];
            $private = preg_match('#^(admin|auth|settings)/#', $page['component']) === 1;
            $pageTitle = ($meta['title'] ?? null) ? $meta['title'].' - '.config('app.name') : config('app.name');
            $pageDescription = $meta['description'] ?? 'Family-raised black, chocolate and yellow Labrador retrievers. Upcoming litters, dates, prices and health testing.';
            $pageImage = $meta['image'] ?? asset('og-image.png');
        @endphp

        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta name="twitter:card" content="summary_large_image">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $pageTitle }}</title>
            @if ($private)
                <meta name="robots" content="noindex, nofollow">
            @else
                <meta name="description" content="{{ $pageDescription }}">
                <link rel="canonical" href="{{ url()->current() }}">
                <meta property="og:type" content="website">
                <meta property="og:url" content="{{ url()->current() }}">
                <meta property="og:title" content="{{ $pageTitle }}">
                <meta property="og:description" content="{{ $pageDescription }}">
                <meta property="og:image" content="{{ $pageImage }}">
            @endif
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
