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
<body class="flex min-h-screen flex-col bg-slate-50 antialiased" x-data="{ reportOpen: false }">
    <header class="bg-slate-900" x-data="{ mobileNavOpen: false }">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 sm:px-6 py-4">
            @if (isset($organization) && filled($organization->logo_path))
                <a href="{{ route('donorportal.dashboard', $organization) }}" wire:navigate class="flex items-center">
                    <img src="{{ route('organization.logo', $organization) }}"
                         alt="{{ $organization->name }}"
                         class="h-9 w-auto object-contain">
                </a>
            @elseif (isset($organization))
                <a href="{{ route('donorportal.dashboard', $organization) }}" wire:navigate
                   class="text-lg font-black text-white [letter-spacing:-0.02em]">
                    {{ $organization->name }}
                </a>
            @else
                <a href="#"
                   class="text-lg font-black text-white [letter-spacing:-0.02em]">
                    Ihsan.
                </a>
            @endif

            {{-- Desktop Nav --}}
            <nav class="hidden md:flex gap-2">
                <a href="{{ route('donorportal.dashboard', $organization) }}" wire:navigate
                   class="rounded-md px-5 py-2.5 text-sm font-medium transition
                   {{ request()->routeIs('donorportal.dashboard') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Dashboard
                </a>
                <a href="{{ route('donorportal.donations', $organization) }}" wire:navigate
                   class="rounded-md px-5 py-2.5 text-sm font-medium transition
                   {{ request()->routeIs('donorportal.donations') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Donations
                </a>
                <a href="{{ route('donorportal.subscriptions', $organization) }}" wire:navigate
                   class="rounded-md px-5 py-2.5 text-sm font-medium transition
                   {{ request()->routeIs('donorportal.subscriptions') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Subscriptions
                </a>
                <a href="{{ route('donorportal.profile', $organization) }}" wire:navigate
                   class="rounded-md px-5 py-2.5 text-sm font-medium transition
                   {{ request()->routeIs('donorportal.profile*') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Profile
                </a>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                @php $headerDonor = request()->donor; @endphp
                @if ($headerDonor && $headerDonor->photo_url)
                    <a href="{{ route('donorportal.profile', $organization) }}" wire:navigate class="h-8 w-8 overflow-hidden rounded-full ring-2 ring-white/20 transition hover:ring-white/40">
                        <img src="{{ $headerDonor->photo_url }}" alt="{{ $headerDonor->name }}" class="h-full w-full object-cover">
                    </a>
                @elseif ($headerDonor)
                    <a href="{{ route('donorportal.profile', $organization) }}" wire:navigate class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 ring-2 ring-white/20 text-xs font-bold text-white transition hover:ring-white/40">
                        {{ strtoupper(substr($headerDonor->name, 0, 1)) }}
                    </a>
                @endif
                <form method="POST" action="{{ route('donorportal.logout', $organization) }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-white/30 transition hover:text-white/60">
                        Sign out
                    </button>
                </form>
            </div>

            {{-- Mobile Hamburger --}}
            <button type="button" @click="mobileNavOpen = !mobileNavOpen"
                    class="md:hidden relative inline-flex items-center justify-center rounded-md p-2 text-white hover:bg-slate-800 transition h-10 w-10">
                <x-heroicon name="bars-3" class="h-6 w-6 transition-opacity duration-200"
                            x-show="!mobileNavOpen"
                            x-cloak
                            x-transition:enter="transition-opacity duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition-opacity duration-200"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0" />
                <x-heroicon name="x-mark" class="h-6 w-6 transition-opacity duration-200 absolute inset-0 m-auto"
                            x-show="mobileNavOpen"
                            x-cloak
                            x-transition:enter="transition-opacity duration-200"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition-opacity duration-200"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0" />
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileNavOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-slate-800 bg-slate-900 px-4 py-3 space-y-1">
            <a href="{{ route('donorportal.dashboard', $organization) }}" wire:navigate @click="mobileNavOpen = false"
               class="block rounded-md px-3 py-2.5 text-sm font-medium transition
               {{ request()->routeIs('donorportal.dashboard') ? 'bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/80 hover:bg-slate-800 hover:text-white' }}">
                Dashboard
            </a>
            <a href="{{ route('donorportal.donations', $organization) }}" wire:navigate @click="mobileNavOpen = false"
               class="block rounded-md px-3 py-2.5 text-sm font-medium transition
               {{ request()->routeIs('donorportal.donations') ? 'bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/80 hover:bg-slate-800 hover:text-white' }}">
                Donations
            </a>
            <a href="{{ route('donorportal.subscriptions', $organization) }}" wire:navigate @click="mobileNavOpen = false"
               class="block rounded-md px-3 py-2.5 text-sm font-medium transition
               {{ request()->routeIs('donorportal.subscriptions') ? 'bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/80 hover:bg-slate-800 hover:text-white' }}">
                Subscriptions
            </a>
            <a href="{{ route('donorportal.profile', $organization) }}" wire:navigate @click="mobileNavOpen = false"
               class="block rounded-md px-3 py-2.5 text-sm font-medium transition
               {{ request()->routeIs('donorportal.profile*') ? 'bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/80 hover:bg-slate-800 hover:text-white' }}">
                Profile
            </a>
            <div class="pt-2 mt-2 border-t border-slate-800">
                <form method="POST" action="{{ route('donorportal.logout', $organization) }}">
                    @csrf
                    <button type="submit" class="w-full text-left rounded-md px-3 py-2.5 text-sm font-medium text-white/80 hover:bg-slate-800 hover:text-white transition">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-5xl flex-1 px-4 sm:px-6 py-8">
        @yield('content')
    </main>

    @if (session('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 5000)"
             x-show="show"
             x-transition:leave="transition ease-in duration-500"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed bottom-6 right-6 z-50 max-w-sm rounded-xl border border-emerald-200 bg-white px-5 py-3.5 shadow-xl">
            <div class="flex items-center gap-3">
                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100">
                    <x-heroicon name="check" class="h-4 w-4 text-emerald-600" />
                </div>
                <p class="text-sm font-semibold text-slate-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 max-w-5xl px-4 sm:px-6 py-4">
            <p class="text-sm text-slate-400 text-center sm:text-left">
                @if (date('Y') > 2026)
                    &copy; 2026 - {{ date('Y') }} {{ $organization->name }}.
                @else
                    &copy; 2026 {{ $organization->name }}.
                @endif
            </p>
                <button type="button" @click="reportOpen = true"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-400 transition hover:text-slate-700">
                <x-heroicon name="question-mark-circle" class="h-3.5 w-3.5" />
                Report a problem
            </button>
        </div>
    </footer>

    {{-- Report a Problem Modal --}}
    <div x-show="reportOpen" x-cloak
         class="fixed inset-0 z-50" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-black/40" @click="reportOpen = false"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-bold text-slate-900">Report a problem</h2>
                    <button type="button" @click="reportOpen = false"
                            class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <x-heroicon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>
                <form method="POST" action="{{ route('donorportal.report-problem', $organization) }}" class="p-6">
                    @csrf
                    <p class="mb-5 text-sm text-slate-600">We value your feedback — it helps us improve your experience with our platform.</p>
                    <label class="mb-1.5 block text-xs font-semibold text-slate-600">Your message</label>
                    <textarea name="message" rows="5" required
                              class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                              placeholder="Describe the issue you're experiencing..."></textarea>
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" @click="reportOpen = false"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-lg px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:shadow-md bg-emerald-600">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
