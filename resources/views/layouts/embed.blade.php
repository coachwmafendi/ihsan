<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-tracking-scripts :organization="$organization ?? null" :configs="$trackingConfigs ?? null" />
        <link rel="preconnect" href="https://js.stripe.com" crossorigin>
        <script src="https://js.stripe.com/v3/"></script>
        @include('partials.cloudflare-analytics')
        @livewireStyles
    </head>
    <body class="bg-transparent text-slate-950 antialiased">
        {{ $slot }}

        @livewireScripts

        <script>
            window.stripePublishableKey = '{{ config('services.stripe.key') }}';

            window.addEventListener('close-popup', function () {
                window.parent.postMessage({ type: 'donation-popup-close' }, '*');
            });

            function ihsanSendHeight() {
                var h = document.documentElement.scrollHeight;
                window.parent.postMessage({ type: 'ihsan:resize', height: h }, '*');
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', ihsanSendHeight);
            } else {
                ihsanSendHeight();
            }

            var ihsanResizeObserver = new ResizeObserver(ihsanSendHeight);
            ihsanResizeObserver.observe(document.body);
        </script>

        @include('partials.donation-step')
    </body>
</html>
