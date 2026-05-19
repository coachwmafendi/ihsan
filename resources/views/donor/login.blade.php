<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Donor Login — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-neutral-100 px-4 antialiased dark:bg-neutral-950">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <a href="{{ route('home') }}" class="text-xl font-semibold text-teal-700">{{ config('app.name') }}</a>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-stone-950">
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Donor Login</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Enter your email to receive a login link.</p>

            @if (session('success'))
                <div class="mt-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('donorportal.send-magic-link') }}" class="mt-6 space-y-4">
                @csrf

                <label class="block">
                    <span class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email</span>
                    <input
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        autofocus
                        class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 dark:border-slate-700 dark:bg-stone-900 dark:text-white"
                        placeholder="donor@example.com"
                    />
                    @error('email')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <button
                    type="submit"
                    class="w-full rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                >
                    Send Login Link
                </button>
            </form>
        </div>
    </div>
</body>
</html>
