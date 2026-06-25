<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <x-tracking-scripts :organization="$organization ?? null" :configs="$trackingConfigs ?? null" />
        <script src="https://js.stripe.com/v3/"></script>
        @livewireStyles
    </head>
    <body class="min-h-screen bg-[#eef1f6] text-slate-950 antialiased">
        {{ $slot }}

        @livewireScripts

        <script>
            window.stripePublishableKey = '{{ config('services.stripe.key') }}';
        </script>

        @include('partials.donation-step')
    </body>
</html>
