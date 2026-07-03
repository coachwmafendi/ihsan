{{-- resources/views/livewire/app/topbar.blade.php --}}
<header class="flex items-center justify-between h-16 px-4 md:px-8 bg-white border-b border-slate-200">
    {{-- Left: Mobile menu toggle + page title context --}}
    <div class="flex items-center gap-3">
        <button
            @click="$dispatch('toggle-sidebar')"
            class="lg:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors"
        >
            <x-heroicon-o-bars-3 class="size-5" />
        </button>

        @if($organization)
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <span class="hidden sm:inline">{{ $organization->name }}</span>
            </div>
        @endif
    </div>

    {{-- Center: Search trigger --}}
    <button
        type="button"
        x-on:click="$dispatch('open-command-palette')"
        class="hidden sm:flex items-center gap-2 w-64 px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-400 hover:border-slate-300 transition-colors"
    >
        <x-heroicon-o-magnifying-glass class="size-4" />
        <span class="flex-1 text-left">Search...</span>
        <kbd class="text-xs border border-slate-200 rounded px-1.5 py-0.5">⌘K</kbd>
    </button>

    {{-- Right: Notifications + User dropdown --}}
    <div class="flex items-center gap-2">
        {{-- Notification bell --}}
        <a
            href="/app/notifications"
            wire:navigate
            class="relative p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors"
        >
            <x-heroicon-o-bell class="size-5" />
            <span
                class="absolute top-1.5 right-1.5 min-w-[1rem] h-4 px-1 flex items-center justify-center rounded-full text-[10px] font-bold text-white bg-teal-600 {{ $unreadCount > 0 ? '' : 'hidden' }}"
                id="notification-badge"
            >
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        </a>

        {{-- User dropdown --}}
        <div x-data="{ open: false }" class="relative">
            <button
                @click="open = !open"
                class="flex items-center gap-2 p-1 pr-2 rounded-lg hover:bg-slate-100 transition-colors"
            >
                @if (auth()->user()?->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()?->name }}" class="size-8 rounded-full object-cover border border-slate-200">
                @else
                    <div class="size-8 rounded-full bg-teal-100 flex items-center justify-center">
                        <span class="text-sm font-medium text-teal-700">{{ substr(auth()->user()?->name ?? 'U', 0, 1) }}</span>
                    </div>
                @endif
                <x-heroicon-o-chevron-down class="size-4 text-slate-400" />
            </button>

            <div
                x-show="open"
                x-cloak
                @click.outside="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 rounded-xl bg-white border border-slate-200 shadow-lg py-2 z-50"
            >
                <div class="px-4 py-2 border-b border-slate-100">
                    <p class="text-sm font-medium text-slate-900">{{ auth()->user()?->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()?->email }}</p>
                </div>

                <a href="/app/settings/account" wire:navigate class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                    <x-heroicon-o-user-circle class="size-4" />
                    Account
                </a>

                <form method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left">
                        <x-heroicon-o-arrow-left-on-rectangle class="size-4" />
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
