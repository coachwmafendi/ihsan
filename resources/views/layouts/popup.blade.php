<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script src="https://js.stripe.com/v3/"></script>
        @livewireStyles
    </head>
    <body class="min-h-screen text-slate-950 antialiased">
        <div
            x-data="popupModal()"
            x-init="init()"
            x-on:close-popup.window="close()"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-black/35 backdrop-blur-[1px]" @click="close()"></div>

            <div class="relative w-full max-w-xl lg:max-w-6xl">
                <button
                    type="button"
                    @click="close()"
                    class="absolute -right-3 -top-3 z-10 flex size-8 items-center justify-center rounded-full bg-white text-slate-400 shadow-lg transition hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Close"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div class="max-h-[calc(100vh-2rem)] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @livewireScripts

        <script>
            window.stripePublishableKey = '{{ config('services.stripe.key') }}';

            function popupModal() {
                return {
                    init() {
                        this.$dispatch('popup-opened');
                    },
                    close() {
                        if (window.parent !== window) {
                            window.parent.postMessage({ type: 'donation-popup-close' }, '*');
                        } else if (window.opener) {
                            window.close();
                        } else {
                            window.history.back();
                        }
                    }
                }
            }
        </script>
    </body>
</html>
