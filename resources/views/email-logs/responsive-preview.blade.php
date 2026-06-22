<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        active: 'mobile',
        darkMode: window.matchMedia('(prefers-color-scheme: dark)').matches,
        toggleTheme() { this.darkMode = ! this.darkMode; }
    }"
    :class="{ 'dark': darkMode }"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $emailLog->subject }} - Responsive preview</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @livewireStyles
    @vite(['resources/css/landing.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased dark:bg-[#0f172a] dark:text-slate-200">
    <div class="flex min-h-screen flex-col items-center">
        {{-- Toolbar --}}
        <div class="sticky top-0 z-50 flex w-full justify-center bg-slate-100/90 px-4 py-4 backdrop-blur dark:bg-[#0f172a]/90">
            <div class="inline-flex items-center gap-2 rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-slate-300 dark:bg-slate-900 dark:ring-slate-800">
                <button
                    type="button"
                    @click="active = 'mobile'"
                    :class="active === 'mobile' ? 'bg-slate-200 text-slate-900 dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200'"
                    class="rounded-lg p-2.5 transition"
                    aria-label="Mobile preview"
                    title="Mobile preview"
                >
                    <x-heroicon-o-device-phone-mobile class="size-6" />
                </button>
                <button
                    type="button"
                    @click="active = 'tablet'"
                    :class="active === 'tablet' ? 'bg-slate-200 text-slate-900 dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200'"
                    class="rounded-lg p-2.5 transition"
                    aria-label="Tablet preview"
                    title="Tablet preview"
                >
                    <x-heroicon-o-device-tablet class="size-6" />
                </button>
                <button
                    type="button"
                    @click="active = 'desktop'"
                    :class="active === 'desktop' ? 'bg-slate-200 text-slate-900 dark:bg-slate-800 dark:text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200'"
                    class="rounded-lg p-2.5 transition"
                    aria-label="Desktop preview"
                    title="Desktop preview"
                >
                    <x-heroicon-o-computer-desktop class="size-6" />
                </button>

                <div class="mx-1 h-6 w-px bg-slate-300 dark:bg-slate-700"></div>

                <button
                    type="button"
                    @click="toggleTheme()"
                    class="rounded-lg p-2.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200"
                    aria-label="Toggle theme"
                    title="Toggle theme"
                >
                    <x-heroicon-o-sun class="size-6" x-show="darkMode" />
                    <x-heroicon-o-moon class="size-6" x-show="! darkMode" />
                </button>
            </div>
        </div>

        {{-- Device frame --}}
        <div class="flex w-full flex-1 justify-center px-4 pb-12 pt-4">
            <div
                class="relative flex flex-col items-center overflow-hidden shadow-2xl transition-all duration-300"
                :class="active === 'desktop'
                    ? 'border border-slate-300 bg-white shadow-xl dark:border-slate-600 dark:bg-slate-800'
                    : 'border-2 border-zinc-400 bg-zinc-600 ring-1 ring-black/10 dark:border-slate-200 dark:bg-slate-800 dark:ring-white/20'"
                :style="{
                    width: active === 'mobile' ? '393px' : (active === 'tablet' ? '834px' : '100%'),
                    maxWidth: active === 'desktop' ? '72rem' : null,
                    height: active === 'mobile' ? '886px' : (active === 'tablet' ? '1234px' : 'calc(100vh - 6rem)'),
                    borderRadius: active === 'mobile' ? '3rem' : (active === 'tablet' ? '2.5rem' : '0.75rem'),
                    padding: active === 'desktop' ? '0px' : '0px'
                }"
            >
                {{-- Dynamic Island (mobile only) --}}
                <div
                    x-show="active === 'mobile'"
                    class="absolute left-1/2 top-3 z-10 h-7 w-28 -translate-x-1/2 rounded-full bg-black ring-2 ring-slate-400/50 dark:bg-black dark:ring-slate-500/60"
                ></div>

                <iframe
                    srcdoc="{{ $html }}"
                    class="block w-full bg-white"
                    :style="{
                        height: active === 'desktop'
                            ? '100%'
                            : 'calc(100% - ' + (active === 'mobile' ? '2.125rem' : '2.5rem') + ')',
                        borderRadius: active === 'mobile' ? '3rem 3rem 0 0' : (active === 'tablet' ? '2.5rem 2.5rem 0 0' : '0.5rem')
                    }"
                    title="{{ $emailLog->subject }}"
                    sandbox
                ></iframe>

                {{-- Home indicator (mobile / tablet only) --}}
                <div
                    x-show="active !== 'desktop'"
                    class="flex w-full shrink-0 items-center justify-center border-t border-zinc-500 bg-zinc-600 dark:border-slate-600 dark:bg-slate-800"
                    :style="{ height: active === 'mobile' ? '2.125rem' : '2.5rem' }"
                >
                    <div class="h-1.5 w-28 rounded-full bg-slate-300/70 dark:bg-slate-400/70"></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
