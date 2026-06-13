{{-- resources/views/components/sidebar-item.blade.php --}}
@props([
    'href',
    'icon',
    'label',
    'active' => false,
])

@php
$iconName = 'heroicon-o-' . $icon;
@endphp

<a
    href="{{ $href }}"
    @if(!str_starts_with($href, 'http')) wire:navigate @endif
    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $active ? 'bg-slate-100 text-slate-900 border-r-2 border-teal-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
>
    <x-dynamic-component :component="$iconName" class="size-5 flex-shrink-0" />
    <span class="truncate">{{ $label }}</span>
</a>
