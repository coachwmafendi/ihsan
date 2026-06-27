<x-layouts::landing>
    <x-slot:title>{{ $config['org']['name'] }} — Ihsan Demo</x-slot:title>

    <div class="min-h-screen bg-slate-50 text-slate-900" x-data="{ demoOpen: false }" @ihsan-open-demo.window="demoOpen = true">
        {{-- Nav --}}
        <nav class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200" x-data="{ open: false }">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-slate-900 font-bold text-lg tracking-tight">
                    <x-app-logo-icon class="h-7 w-auto" />
                    <span>{{ $config['org']['name'] }}</span>
                </a>

                <div class="hidden md:flex items-center gap-6 text-sm">
                    @foreach ($config['nav'] as $item)
                        <a href="{{ $item['href'] }}" class="text-slate-600 hover:text-slate-900 transition-colors">{{ $item['label'] }}</a>
                    @endforeach
                    <span class="text-xs font-medium text-slate-500 border border-slate-200 rounded-full px-3 py-1.5">Demo oleh Ihsan</span>
                </div>

                <div class="flex md:hidden items-center gap-3">
                    <button @click="open = !open" class="p-2 text-slate-600 hover:text-slate-900 transition-colors" aria-label="Menu">
                        <svg x-show="!open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        <svg x-show="open" style="display:none" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <div x-show="open" style="display:none" x-transition class="md:hidden border-t border-slate-200 bg-white/95 backdrop-blur-lg">
                <div class="max-w-6xl mx-auto px-4 py-4 flex flex-col gap-1">
                    @foreach ($config['nav'] as $item)
                        <a href="{{ $item['href'] }}" @click="open = false" class="text-slate-700 hover:text-slate-900 hover:bg-slate-100 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </div>
        </nav>

        {{-- Hero --}}
        <section id="donate" class="relative pt-32 pb-20 lg:pt-44 lg:pb-28 overflow-hidden bg-slate-50">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 via-transparent to-amber-500/10 pointer-events-none"></div>
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <span class="inline-block text-emerald-700 font-semibold text-xs sm:text-sm tracking-widest uppercase mb-6 bg-emerald-100 border border-emerald-200 rounded-full px-4 py-2">
                    Pembukaan Semula 2026
                </span>

                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold text-slate-900 leading-tight tracking-tight">
                    Sumbangan Anda,<br>
                    <span class="text-emerald-600">Masa Depan Mereka</span>
                </h1>

                <p class="mt-6 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    {{ $config['org']['tagline'] }}
                </p>

                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <script src="/e/widget.js"
                            data-demo="true"
                            data-token="{{ $tokens['button'] }}"
                            data-api-base="{{ $config['widget']['base_url'] }}"
                            data-type="button"
                            data-text="{{ $config['widget']['button_text'] }}"
                            data-color="{{ $config['widget']['color'] }}"
                            data-action="checkout_modal">
                    </script>
                    <a href="#programs" class="text-slate-600 hover:text-slate-900 transition-colors text-base font-medium">Ketahui program kami &rarr;</a>
                </div>

                <div class="mt-16 rounded-2xl overflow-hidden border border-slate-200 shadow-2xl">
                    <img src="{{ $config['images']['hero'] }}" alt="{{ $config['org']['name'] }}" class="w-full h-auto object-cover">
                </div>
            </div>
        </section>

        {{-- Stats --}}
        <section class="border-y border-slate-200 bg-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 sm:gap-12 text-center">
                    @foreach ($config['stats'] as $stat)
                        <div>
                            <div class="text-3xl sm:text-4xl font-extrabold text-emerald-600">{{ $stat['value'] }}</div>
                            <div class="mt-2 text-sm text-slate-500">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Programs --}}
        <section id="programs" class="py-20 sm:py-28 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">Program Kami</h2>
                    <p class="mt-4 text-slate-600 max-w-2xl mx-auto">Tiga teras utama yang memastikan setiap pelajar berkembang secara holistik.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($config['programs'] as $program)
                        <div class="group rounded-2xl bg-white border border-slate-200 overflow-hidden hover:border-emerald-400 transition-colors shadow-sm hover:shadow-md">
                            <div class="aspect-[4/3] overflow-hidden bg-slate-100">
                                <img src="{{ $program['image'] }}" alt="{{ $program['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                            <div class="p-8">
                                <h3 class="text-xl font-semibold text-slate-900 mb-3">{{ $program['title'] }}</h3>
                                <p class="text-slate-600 leading-relaxed">{{ $program['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Impact story --}}
        <section id="impact" class="py-20 sm:py-28 border-y border-slate-200 bg-slate-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="rounded-2xl overflow-hidden aspect-[4/3] border border-white shadow-lg bg-white">
                        <img src="{{ $config['impact']['image'] }}" alt="Impak {{ $config['org']['name'] }}" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-6">{{ $config['impact']['title'] }}</h2>
                        <p class="text-lg text-slate-600 leading-relaxed mb-8">{{ $config['impact']['body'] }}</p>
                        <script src="/e/widget.js"
                                data-demo="true"
                                data-token="{{ $tokens['button'] }}"
                                data-api-base="{{ $config['widget']['base_url'] }}"
                                data-type="button"
                                data-text="Sumbang Sekarang"
                                data-color="{{ $config['widget']['color'] }}"
                                data-action="checkout_modal">
                        </script>
                    </div>
                </div>
            </div>
        </section>

        {{-- Monthly giving --}}
        <section class="py-20 sm:py-28 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="order-2 lg:order-1">
                        <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-6">{{ $config['monthly']['title'] }}</h2>
                        <p class="text-lg text-slate-600 leading-relaxed mb-8">{{ $config['monthly']['body'] }}</p>
                        <div class="flex flex-wrap gap-4">
                            @foreach ($config['monthly']['amounts'] as $amount)
                                <button type="button" @click="$dispatch('ihsan-open-demo')" class="px-8 py-4 rounded-full bg-white border border-slate-200 text-slate-900 font-semibold hover:bg-emerald-500 hover:border-emerald-500 hover:text-white transition-colors shadow-sm">
                                    {{ $amount }} / bulan
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="order-1 lg:order-2 rounded-2xl overflow-hidden aspect-[4/3] border border-slate-200 shadow-lg bg-slate-100">
                        <img src="{{ $config['monthly']['image'] }}" alt="{{ $config['monthly']['title'] }}" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
        </section>

        {{-- Inline donation form --}}
        <section id="donate-form" class="py-20 sm:py-28 border-y border-slate-200 bg-slate-100">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">Buat Sumbangan</h2>
                    <p class="mt-4 text-slate-600">Borang demo yang dipacak di website sebenar menggunakan Ihsan.</p>
                </div>

                @if ($tokens['form'])
                    <div class="mx-auto max-w-xl bg-white rounded-2xl p-6 border border-slate-200 shadow-lg">
                        <script src="/e/widget.js"
                                data-token="{{ $tokens['form'] }}"
                                data-api-base="{{ $config['widget']['base_url'] }}"
                                data-type="form"
                                data-height="640">
                        </script>
                    </div>
                    <p class="text-center text-xs text-slate-500 mt-6">Ini adalah borang demo. Jika di Website sebenar, bayaran akan diproses melalui Ihsan.</p>
                @else
                    <div class="rounded-2xl bg-white border border-slate-200 p-8 sm:p-10 shadow-lg max-w-xl mx-auto">
                        <p class="text-slate-600 text-center">Borang demo belum tersedia. Jalankan <code class="text-emerald-600">php artisan db:seed --class=MadrasahDemoSeeder</code> untuk membenamkan borang sebenar.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Footer --}}
        <footer class="py-16 border-t border-slate-200 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between gap-10">
                    <div>
                        <a href="{{ route('home') }}" class="flex items-center gap-2 text-slate-900 font-bold text-lg tracking-tight mb-4">
                            <x-app-logo-icon class="h-7 w-auto" />
                            <span>{{ $config['org']['name'] }}</span>
                        </a>
                        <p class="text-slate-600 text-sm whitespace-pre-line">{{ $config['footer']['address'] }}</p>
                    </div>

                    <div class="flex gap-6">
                        @foreach ($config['footer']['socials'] as $social)
                            <a href="{{ $social['href'] }}" class="text-slate-600 hover:text-emerald-600 transition-colors text-sm">{{ $social['label'] }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-slate-200 text-center text-slate-500 text-sm">
                    &copy; {{ date('Y') }} {{ $config['org']['name'] }}. Dikuasakan oleh <a href="{{ route('home') }}" class="text-slate-600 hover:text-slate-900">Ihsan</a>.
                </div>
            </div>
        </footer>

        {{-- Floating button --}}
        <script src="/e/widget.js"
                data-demo="true"
                data-token="{{ $tokens['floating_button'] }}"
                data-api-base="{{ $config['widget']['base_url'] }}"
                data-type="floating_button"
                data-text="{{ $config['widget']['floating_text'] }}"
                data-color="{{ $config['widget']['color'] }}"
                data-position="{{ $config['widget']['position'] }}"
                data-action="checkout_modal">
        </script>

        {{-- Demo modal --}}
        <div x-show="demoOpen" style="display: none" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-[2147483647] flex items-center justify-center bg-slate-900/50 p-4" aria-modal="true" role="dialog" @keydown.escape.window="demoOpen = false">
            <div class="relative w-full max-w-md rounded-2xl bg-white border border-slate-200 p-8 shadow-2xl" @click.outside="demoOpen = false">
                <button type="button" @click="demoOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-900" aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>

                <div class="mx-auto w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mb-6">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                </div>

                <h3 class="text-xl font-bold text-slate-900 text-center mb-3">Ini Adalah Demo Sahaja</h3>
                <p class="text-slate-600 text-center mb-6">Jika ini adalah site sebenar, derma anda akan diproses secara selamat melalui Ihsan dan masuk terus ke akaun institusi.</p>

                <div class="flex justify-center">
                    <button type="button" @click="demoOpen = false" class="px-6 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold transition-colors">
                        Faham
                    </button>
                </div>
            </div>
        </div>

        <script>
            window.__ihsanOpenDemoCheckout = function () {
                window.dispatchEvent(new CustomEvent('ihsan-open-demo'));
            };
        </script>
    </div>
</x-layouts::landing>
