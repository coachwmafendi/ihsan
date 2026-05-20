<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://js.stripe.com/v3/"></script>
        @livewireStyles
    </head>
    <body class="bg-transparent text-slate-950 antialiased">
        {{ $slot }}

        @livewireScripts

        @env('local')
        <script>
            window.stripePublishableKey = '{{ config('services.stripe.key') }}';
        </script>
        @endenv
    </body>
</html>
