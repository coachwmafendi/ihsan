<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? __('site.title') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Runs before first paint, or a visitor who chose light watches the page
         flash dark first. Pages with no dark: classes ignore the class. --}}
    <script>
        (() => {
            const stored = localStorage.getItem('theme');
            const dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @vite(['resources/css/landing.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @include('partials.google-tag-manager')
    @include('partials.cloudflare-analytics')
</head>
<body class="{{ $bodyClass ?? 'bg-canvas text-slate-200' }} font-sans antialiased">
@include('partials.google-tag-manager-noscript')

{{ $slot }}
</body>
</html>
