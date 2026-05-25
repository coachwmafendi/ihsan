<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 px-4 antialiased">
    <div class="w-full max-w-xs">
        <div class="mb-8">
            <p class="text-2xl font-black text-slate-900 [letter-spacing:-0.03em]">Ihsan.</p>
            <p class="mt-1 text-xs text-slate-400">Your giving, your way.</p>
        </div>

        <div class="rounded-2xl bg-white p-8 shadow-[0_4px_24px_rgba(15,23,42,0.08)]"
             style="border:1.5px solid #e2e8f0;">
            <h1 class="text-base font-black text-slate-900">Welcome back</h1>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
                Enter your email and we'll send a magic link — no password needed.
            </p>

            @if (session('success'))
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-semibold leading-relaxed text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs leading-relaxed text-red-600">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('donorportal.send-magic-link') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700">
                        Email address
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        autofocus
                        value="{{ old('email') }}"
                        placeholder="donor@example.com"
                        class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        style="border-width:1.5px;"
                    />
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 active:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2"
                >
                    Send Login Link →
                </button>
            </form>
        </div>
    </div>
</body>
</html>
