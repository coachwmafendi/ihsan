<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Virtual Terminal' }}</title>

    @fonts
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="min-h-screen bg-[#f7f7fb] font-sans text-slate-900 antialiased">
    <main class="mx-auto min-h-screen max-w-7xl p-6 md:p-8">
        {{ $slot }}
    </main>

    @livewireScripts
    @fluxScripts
</body>
</html>