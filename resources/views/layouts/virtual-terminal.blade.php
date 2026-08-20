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

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @include('partials.google-tag-manager')
    @include('partials.cloudflare-analytics')
</head>
<body class="min-h-screen bg-[#f7f7fb] font-sans text-slate-900 antialiased">
    @include('partials.google-tag-manager-noscript')
    <main class="mx-auto min-h-screen max-w-7xl p-6 md:p-8">
        {{ $slot }}
    </main>

    {{-- Flux Toast Container --}}
    <flux:toast.group position="top right">
        <flux:toast variant="success" icon="heroicon-o-check-circle" />
        <flux:toast variant="danger" icon="heroicon-o-x-circle" />
        <flux:toast variant="warning" icon="heroicon-o-exclamation-triangle" />
        <flux:toast variant="info" icon="heroicon-o-information-circle" />
    </flux:toast.group>

    {{-- Bridge: Convert Livewire 'notify' events to Flux toasts --}}
    <script>
        document.addEventListener('livewire:init', () => {
            document.addEventListener('notify', (e) => {
                const detail = e.detail || {};

                document.dispatchEvent(new CustomEvent('toast-show', {
                    detail: {
                        slots: {
                            heading: detail.heading,
                            text: detail.message || detail.text,
                        },
                        dataset: {
                            variant: detail.variant || 'info',
                        },
                        duration: detail.duration,
                    },
                }));
            });
        }, { once: true });
    </script>

    @livewireScripts
    @fluxScripts
</body>
</html>