<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Donor Portal') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 antialiased">
    <header class="bg-slate-900">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-3">
            <a href="{{ route('donorportal.dashboard') }}"
               class="text-sm font-black text-white [letter-spacing:-0.02em]">
                Ihsan.
            </a>
            <nav class="flex gap-1">
                <a href="{{ route('donorportal.dashboard') }}"
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.dashboard') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Dashboard
                </a>
                <a href="{{ route('donorportal.donations') }}"
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.donations') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Donations
                </a>
                <a href="{{ route('donorportal.subscriptions') }}"
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.subscriptions') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Subscriptions
                </a>
            </nav>
            <div class="flex items-center gap-3">
                @php
                    $nameParts = array_values(array_filter(explode(' ', trim($donor->name))));
                    $initials = strtoupper(substr($nameParts[0] ?? '?', 0, 1));
                    if (count($nameParts) > 1) {
                        $initials .= strtoupper(substr(end($nameParts), 0, 1));
                    }
                @endphp
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-[10px] font-bold text-white">
                    {{ $initials }}
                </div>
                <form method="POST" action="{{ route('donorportal.logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-white/30 transition hover:text-white/60">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
