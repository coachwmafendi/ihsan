<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Donor Portal') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 antialiased">
    <header class="border-b border-stone-200/70 bg-white/90 backdrop-blur-sm">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-5">
            <a href="{{ route('donorportal.donations') }}" class="text-xl font-semibold tracking-tight text-emerald-700">
                {{ config('app.name') }}
            </a>
            <a href="{{ route('donorportal.logout') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-stone-500 transition hover:bg-stone-100 hover:text-stone-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                Logout
            </a>
        </div>
    </header>

    <div class="mx-auto max-w-4xl px-6 py-10">
        <div class="mb-10">
            <nav class="flex gap-1 rounded-xl bg-stone-100/80 p-1.5">
                <a href="{{ route('donorportal.donations') }}"
                   class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-medium transition
                   {{ request()->routeIs('donorportal.donations') ? 'bg-white text-emerald-700 shadow-sm' : 'text-stone-500 hover:text-stone-700' }}">
                    Donations
                </a>
                <a href="{{ route('donorportal.subscriptions') }}"
                   class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-medium transition
                   {{ request()->routeIs('donorportal.subscriptions') ? 'bg-white text-emerald-700 shadow-sm' : 'text-stone-500 hover:text-stone-700' }}">
                    Subscriptions
                </a>
            </nav>
        </div>

        @if (session('success'))
            <div class="mb-8 rounded-xl bg-emerald-50 px-5 py-4 text-sm text-emerald-700 ring-1 ring-emerald-100">{{ session('success') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
