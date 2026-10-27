<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $pageTitle = trim(html_entity_decode(strip_tags($title ?? __('site.title'))));
        $pageDescription = trim(html_entity_decode(strip_tags($description ?? __('site.description'))));
        $pageImage = asset('images/og-image.png');
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#0f172a">

    {{-- Link previews (WhatsApp, Facebook, LinkedIn, X, Telegram). --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Ihsan">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ms' ? 'ms_MY' : 'en_MY' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:image:secure_url" content="{{ $pageImage }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $pageTitle }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

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
