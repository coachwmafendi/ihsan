{{-- resources/views/components/sidebar-dropdown.blade.php --}}
@props([
    'icon',
    'label',
    'active' => false,
])

@php
$iconName = 'heroicon-o-' . $icon;
@endphp

<div
    x-data="{ dropdownOpen: {{ $active ? 'true' : 'false' }} }"
    x-init="$watch('$store.sidebar.collapsed', (collapsed) => { if (collapsed) dropdownOpen = false; })"
    class="space-y-0.5"
>
    <x-sidebar-tooltip :text="$label">
        <button
            @click="dropdownOpen = ! dropdownOpen"
            class="flex w-full items-center rounded-lg py-2 text-sm font-medium transition-colors duration-150 {{ $active ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
            :class="$store.sidebar.collapsed ? 'justify-center px-2' : 'justify-between px-3'"
        >
            <div class="flex items-center"
                 :class="$store.sidebar.collapsed ? '' : 'gap-3'">
                <x-dynamic-component :component="$iconName" class="size-5 flex-shrink-0" />
                <span
                    class="truncate whitespace-nowrap"
                    x-show="! $store.sidebar.collapsed"
                    x-cloak
                >{{ $label }}</span>
            </div>
            <x-heroicon-o-chevron-down
                class="size-4 transition-transform"
                :class="dropdownOpen ? 'rotate-180' : ''"
                x-show="! $store.sidebar.collapsed"
                x-cloak
            />
        </button>
    </x-sidebar-tooltip>

    <div
        x-show="dropdownOpen && ! $store.sidebar.collapsed"
        x-collapse
        class="ml-4 space-y-0.5 border-l border-slate-200 pl-3"
    >
        {{ $slot }}
    </div>
</div>
