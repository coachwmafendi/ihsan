<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            {{-- Left: Form --}}
            <div class="flex flex-col px-12 py-16 sm:px-20">
                <a href="{{ route('home') }}" wire:navigate class="mb-10 inline-flex items-center gap-2.5">
                    <x-app-logo-icon class="h-9 w-9" />
                    <span class="text-2xl font-semibold tracking-tight text-teal-700 dark:text-teal-400">ihsan</span>
                </a>

                <div class="flex flex-col gap-8 flex-1 max-w-[400px]">
                    {{ $slot }}
                </div>

                <p class="mt-auto pt-12 text-sm text-neutral-400">
                    &copy; {{ date('Y') }} {{ config('app.name') }}
                </p>
            </div>

            {{-- Right: Visual panel --}}
            <div class="relative hidden lg:flex flex-col overflow-hidden bg-[#0a0f1e]">
                {{-- Gradient orbs --}}
                <div class="absolute inset-0">
                    <div class="absolute top-0 right-0 h-[600px] w-[600px] rounded-full bg-teal-500/20 blur-[120px]"></div>
                    <div class="absolute bottom-0 left-0 h-[400px] w-[400px] rounded-full bg-blue-500/10 blur-[100px]"></div>
                </div>

                {{-- Content --}}
                <div class="relative z-10 flex flex-col h-full p-14">
                    {{-- Mock app card --}}
                    <div class="mt-auto mb-auto">
                        <div class="rounded-2xl bg-white/5 ring-1 ring-white/10 backdrop-blur-sm p-6 max-w-sm">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="h-9 w-9 rounded-xl bg-teal-500/20 ring-1 ring-teal-400/30 flex items-center justify-center">
                                    <svg class="h-4 w-4 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white">Total Donations</p>
                                    <p class="text-xs text-white/40">This month</p>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-white mb-1">RM 48,250</p>
                            <p class="text-sm text-teal-400">↑ 12% from last month</p>

                            <div class="mt-5 pt-5 border-t border-white/10 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-white/40 mb-1">Donors</p>
                                    <p class="text-lg font-semibold text-white">1,284</p>
                                </div>
                                <div>
                                    <p class="text-xs text-white/40 mb-1">Campaigns</p>
                                    <p class="text-lg font-semibold text-white">24</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl bg-white/5 ring-1 ring-white/10 backdrop-blur-sm p-5 max-w-sm">
                            <p class="text-xs text-white/40 mb-3">Recent donations</p>
                            <div class="flex flex-col gap-3">
                                @foreach ([['Ahmad R.', 'RM 100', '2m ago'], ['Siti N.', 'RM 50', '8m ago'], ['Muhammad A.', 'RM 250', '15m ago']] as [$name, $amount, $time])
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2.5">
                                            <div class="h-7 w-7 rounded-full bg-white/10 flex items-center justify-center text-[11px] font-medium text-white/70">
                                                {{ mb_substr($name, 0, 1) }}
                                            </div>
                                            <span class="text-sm text-white/70">{{ $name }}</span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-white">{{ $amount }}</p>
                                            <p class="text-[11px] text-white/30">{{ $time }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <p class="text-sm text-white/25 mt-auto">
                        Empowering generosity, one donation at a time.
                    </p>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
