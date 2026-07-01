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
        class="flex items-center rounded-lg py-2 text-sm font-medium transition-colors duration-150 {{ $active ? 'bg-slate-100 text-slate-900 border-r-2 border-teal-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
        :class="$store.sidebar.collapsed ? 'justify-center px-2' : 'gap-3 px-3'"
    >
        <x-dynamic-component :component="$iconName" class="size-5 flex-shrink-0" />
        <span
            class="truncate whitespace-nowrap"
            x-show="! $store.sidebar.collapsed"
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
