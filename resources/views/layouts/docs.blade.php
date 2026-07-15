<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ filled($title ?? null) ? $title.' - Docs' : 'Docs' }} - {{ config('app.name') }}</title>

    @fonts
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js" defer></script>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased"
      x-data="{ sidebarOpen: false }"
      x-on:keydown.escape.window="sidebarOpen = false">

    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button type="button" class="lg:hidden p-2 -ml-2 text-slate-600" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu" aria-controls="docs-sidebar" :aria-expanded="sidebarOpen">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-lg text-slate-900">
                    <x-app-logo-icon class="h-6 w-auto" />
                    <span>{{ config('app.name') }}</span>
                </a>
            </div>

            <div class="flex items-center gap-6">
                <a href="{{ route('docs.show') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Docs</a>
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Sign in</a>
                <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ms' ? 'en' : 'ms']) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                    @lang('nav.switch_language')
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <div class="flex gap-8">
            <aside id="docs-sidebar"
                   class="fixed inset-y-0 left-0 z-30 w-64 transform -translate-x-full lg:static lg:translate-x-0 bg-white lg:bg-transparent border-r border-slate-200 lg:border-0 overflow-y-auto p-4 lg:p-0 transition-transform"
                   :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   @click.outside="sidebarOpen = false">
                <x-docs.sidebar :path="$path ?? 'index'" />
            </aside>

            <div class="fixed inset-0 z-20 bg-black/20 lg:hidden"
                 x-show="sidebarOpen"
                 @click="sidebarOpen = false"></div>

            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    @fluxScripts
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.hljs) {
                document.querySelectorAll('pre code').forEach(window.hljs.highlightElement);
            }
        });
    </script>
</body>
</html>
