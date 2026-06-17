<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    @fonts
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <x-app-shell>
        {{ $slot }}
    </x-app-shell>

    @include('components.ui._tooltip-script')
    @livewireScripts
    @fluxScripts

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
</body>
</html>
