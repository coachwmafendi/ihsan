@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'wireClick' => null,
    'disabled' => false,
    'target' => null,
])

@php
$variants = [
    'primary' => 'bg-teal-600 text-white hover:bg-teal-700 focus-visible:ring-teal-600',
    'secondary' => 'bg-slate-100 text-slate-900 hover:bg-slate-200 focus-visible:ring-slate-500',
    'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 focus-visible:ring-slate-500',
    'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:ring-slate-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-600',
];

$sizes = [
    'sm' => 'h-8 px-3 text-xs',
    'md' => 'h-9 px-4 py-2 text-sm',
    'lg' => 'h-10 px-6 py-2.5 text-sm',
];

$classes = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 ';
$classes .= ($variants[$variant] ?? $variants['primary']) . ' ';
$classes .= ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href && !$wireClick)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if($target) target="{{ $target }}" @endif>
        {{ $slot }}
    </a>
@elseif($wireClick)
    <button type="{{ $type }}" wire:click="{{ $wireClick }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($disabled)>
        {{ $slot }}
    </button>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @disabled($disabled)>
        {{ $slot }}
    </button>
@endif
