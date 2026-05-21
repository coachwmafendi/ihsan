<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Donor Login — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-stone-50 to-stone-100 px-4 antialiased">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <a href="{{ route('home') }}">
                <img src="{{ asset('logo-ihsan.png') }}" alt="{{ config('app.name') }}" class="mx-auto h-10 w-auto" />
            </a>
            <p class="mt-1 text-sm text-stone-400">Your giving, your way.</p>
        </div>

        <div class="rounded-2xl border border-stone-200/70 bg-white/90 p-10 shadow-lg shadow-stone-200/50 backdrop-blur-sm">
            <h1 class="text-xl font-semibold text-stone-900">Welcome back</h1>
            <p class="mt-2 text-sm leading-relaxed text-stone-500">Enter your email and we'll send you a magic link to sign in — no password needed.</p>

            @if (session('success'))
                <div class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm leading-relaxed text-emerald-700 ring-1 ring-emerald-100">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mt-6 rounded-xl bg-red-50 p-4 text-sm leading-relaxed text-red-600 ring-1 ring-red-100">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('donorportal.send-magic-link') }}" class="mt-8 space-y-6">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-stone-700">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        autofocus
                        class="mt-1.5 block w-full rounded-lg border border-stone-300 px-3.5 py-2.5 text-sm text-stone-900 shadow-sm transition placeholder:text-stone-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="donor@example.com"
                    />
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:bg-emerald-800"
                >
                    Send Login Link
                </button>
            </form>
        </div>
    </div>
</body>
</html>
