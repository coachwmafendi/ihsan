<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Donor Portal') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 antialiased dark:bg-neutral-950">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-lg font-semibold text-teal-700">{{ config('app.name') }}</a>
            <a href="{{ route('donor.logout') }}" class="text-sm text-slate-500 hover:text-slate-700">Logout</a>
        </div>

        <nav class="mb-6 flex gap-4 border-b border-slate-200 pb-4">
            <a href="{{ route('donor.donations') }}" class="text-sm font-medium {{ request()->routeIs('donor.donations') ? 'text-teal-700' : 'text-slate-500' }}">
                Donation History
            </a>
            <a href="{{ route('donor.subscriptions') }}" class="text-sm font-medium {{ request()->routeIs('donor.subscriptions') ? 'text-teal-700' : 'text-slate-500' }}">
                Subscriptions
            </a>
        </nav>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
