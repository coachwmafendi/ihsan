{{-- resources/views/components/donor-bottom-nav.blade.php --}}
@props(['organization'])

@php
    $items = [
        ['route' => 'donorportal.dashboard', 'pattern' => 'donorportal.dashboard', 'icon' => 'home', 'label' => 'Dashboard'],
        ['route' => 'donorportal.donations', 'pattern' => 'donorportal.donations*', 'icon' => 'banknotes', 'label' => 'Donations'],
        ['route' => 'donorportal.subscriptions', 'pattern' => 'donorportal.subscriptions*', 'icon' => 'arrow-path', 'label' => 'Recurring'],
        ['route' => 'donorportal.profile', 'pattern' => 'donorportal.profile*', 'icon' => 'user-circle', 'label' => 'Profile'],
    ];
@endphp

<nav
    data-test="donor-bottom-nav"
    class="fixed inset-x-0 bottom-0 z-30 flex items-stretch border-t border-slate-800 bg-slate-900/95 backdrop-blur md:hidden"
    style="padding-bottom: env(safe-area-inset-bottom)"
>
    @foreach ($items as $item)
        @php $isCurrent = request()->routeIs($item['pattern']); @endphp

        <a
            href="{{ route($item['route'], $organization) }}"
            wire:navigate
            class="flex flex-1 flex-col items-center justify-center gap-1 px-1 py-2 text-[0.625rem] font-medium transition {{ $isCurrent ? 'text-emerald-400' : 'text-white/50' }}"
            @if ($isCurrent) aria-current="page" @endif
        >
            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-6" />
            <span class="leading-none">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
