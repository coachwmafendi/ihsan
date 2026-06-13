{{-- resources/views/components/sidebar-dropdown.blade.php --}}
@props([
    'icon',
    'label',
    'active' => false,
])

@php
$iconName = 'heroicon-o-' . $icon;
@endphp

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="space-y-0.5">
    <button
        @click="open = !open"
        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $active ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
    >
        <div class="flex items-center gap-3">
            <x-dynamic-component :component="$iconName" class="size-5 flex-shrink-0" />
            <span class="truncate">{{ $label }}</span>
        </div>
        <x-heroicon-o-chevron-down class="size-4 transition-transform" :class="open ? 'rotate-180' : ''" />
    </button>
    
    <div x-show="open" x-collapse class="ml-4 space-y-0.5 border-l border-slate-200 pl-3">
        {{ $slot }}
    </div>
</div>
