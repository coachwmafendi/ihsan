<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script src="https://js.stripe.com/v3/"></script>
        @livewireStyles
    </head>
    <body class="min-h-screen text-slate-950 antialiased">
        <div id="popup-container" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div id="popup-backdrop" class="absolute inset-0 bg-black/35 backdrop-blur-[1px]" onclick="closePopup()"></div>

            <div class="relative w-full max-w-xl lg:max-w-6xl">
                <button
                    type="button"
                    onclick="closePopup()"
                    class="absolute right-3 top-3 z-10 flex size-8 items-center justify-center rounded-full bg-white text-slate-400 shadow-lg transition hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Close"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <div id="popup-inner" class="max-h-[calc(100vh-2rem)] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @livewireScripts

        <script>
            window.stripePublishableKey = '{{ config('services.stripe.key') }}';

            if (window.self !== window.top) {
                var container = document.getElementById('popup-container');
                container.className = 'h-full w-full';
                container.style.removeProperty('position');
                container.style.removeProperty('z-index');

                var backdrop = document.getElementById('popup-backdrop');
                if (backdrop) backdrop.remove();

                container.querySelector('button[onclick="closePopup()"]')?.remove();

                var inner = document.getElementById('popup-inner');
                if (inner) {
                    inner.className = 'h-full w-full';
                    inner.style.removeProperty('max-height');
                    inner.style.removeProperty('overflow');
                    inner.style.removeProperty('border-radius');
                    inner.style.removeProperty('box-shadow');
                }

                document.body.classList.add('bg-white');
            }

            document.addEventListener('close-popup', closePopup);

            function closePopup() {
                if (window.parent !== window) {
                    window.parent.postMessage({ type: 'donation-popup-close' }, '*');
                } else if (window.opener) {
                    window.close();
                } else {
                    window.history.back();
                }
            }
        </script>
    </body>
</html>
