{{-- resources/views/components/sidebar-item.blade.php --}}
@props([
    'href',
    'icon',
    'label',
    'active' => false,
    'target' => null,
])

@php
$iconName = str_contains($icon, '.') ? $icon : 'heroicon-o-' . $icon;
@endphp

<x-sidebar-tooltip :text="$label">
    <a
        href="{{ $href }}"
        @if($target) target="{{ $target }}" rel="noopener noreferrer" @endif
        @if(! $target && !str_starts_with($href, 'http')) wire:navigate @endif
        class="flex items-center rounded-lg py-2 text-sm font-medium transition-[color,background-color,padding,gap] duration-300 ease-in-out motion-reduce:transition-none {{ $active ? 'bg-slate-100 text-blue-600 border-r-2 border-teal-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
        :class="$store.sidebar.collapsed ? 'justify-center px-2' : 'gap-3 px-3'"
    >
        <x-dynamic-component :component="$iconName" class="size-5 flex-shrink-0" />
        <span
            class="truncate whitespace-nowrap"
            x-show="! $store.sidebar.collapsed"
            x-transition:enter="transition-opacity ease-out duration-200 delay-150 motion-reduce:transition-none"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-100 motion-reduce:transition-none"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
        >{{ $label }}</span>
        @if($target === '_blank')
            <x-heroicon-o-arrow-top-right-on-square
                aria-label="Opens in new tab"
                class="size-4 flex-shrink-0 text-slate-400"
                x-show="! $store.sidebar.collapsed"
                x-cloak
            />
        @endif
    </a>
</x-sidebar-tooltip>
