<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Donor Portal') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-slate-50 antialiased">
    <header class="bg-slate-900">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-3">
            @if (isset($organization) && filled($organization->logo_path))
                <a href="{{ route('donorportal.dashboard', $organization) }}" wire:navigate class="flex items-center">
                    <img src="{{ route('organization.logo', $organization) }}"
                         alt="{{ $organization->name }}"
                         class="h-8 w-auto object-contain">
                </a>
            @elseif (isset($organization))
                <a href="{{ route('donorportal.dashboard', $organization) }}" wire:navigate
                   class="text-sm font-black text-white [letter-spacing:-0.02em]">
                    {{ $organization->name }}
                </a>
            @else
                <a href="#"
                   class="text-sm font-black text-white [letter-spacing:-0.02em]">
                    Ihsan.
                </a>
            @endif
            <nav class="flex gap-1">
                <a href="{{ route('donorportal.dashboard', $organization) }}" wire:navigate
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.dashboard') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Dashboard
                </a>
                <a href="{{ route('donorportal.donations', $organization) }}" wire:navigate
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.donations') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Donations
                </a>
                <a href="{{ route('donorportal.subscriptions', $organization) }}" wire:navigate
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.subscriptions') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Subscriptions
                </a>
                <a href="{{ route('donorportal.profile', $organization) }}" wire:navigate
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.profile*') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Profile
                </a>
            </nav>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('donorportal.logout', $organization) }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-white/30 transition hover:text-white/60">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-8">
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
