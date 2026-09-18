{{-- resources/views/components/bottom-nav.blade.php --}}
@php
    $isActive = fn (string $path): bool => request()->is(trim($path, '/')) || request()->is(trim($path, '/').'/*');

    $items = [
        ['href' => '/dashboard', 'icon' => 'home', 'label' => 'Dashboard', 'active' => $isActive('/dashboard')],
        ['href' => '/donations', 'icon' => 'banknotes', 'label' => 'Donations', 'active' => $isActive('/donations')],
        ['href' => '/recurring-plans', 'icon' => 'arrow-path', 'label' => 'Recurring', 'active' => $isActive('/recurring-plans') || $isActive('/subscriptions')],
        ['href' => '/supporters', 'icon' => 'users', 'label' => 'Supporters', 'active' => $isActive('/supporters')],
    ];
@endphp

<nav
    data-test="app-bottom-nav"
    class="fixed inset-x-0 bottom-0 z-30 flex items-stretch border-t border-slate-200 bg-white/95 backdrop-blur lg:hidden"
    style="padding-bottom: env(safe-area-inset-bottom)"
>
    @foreach ($items as $item)
        <a
            href="{{ $item['href'] }}"
            wire:navigate
            class="flex flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[0.625rem] font-medium transition-colors {{ $item['active'] ? 'text-teal-700' : 'text-slate-500' }}"
            @if ($item['active']) aria-current="page" @endif
        >
            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-6" />
            <span class="leading-none">{{ $item['label'] }}</span>
        </a>
    @endforeach

    {{-- Everything else already lives in the sidebar drawer. --}}
    <button
        type="button"
        x-data
        @click="$dispatch('toggle-sidebar')"
        class="flex flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[0.625rem] font-medium text-slate-500 transition-colors"
    >
        <x-heroicon-o-bars-3 class="size-6" />
        <span class="leading-none">More</span>
    </button>
</nav>
