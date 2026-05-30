@props([
    'icon' => null,
    'label',
    'value',
    'tone' => 'stone',
])

@php
    $toneClasses = [
        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'red' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
        'teal' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
        'stone' => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300',
    ][$tone] ?? 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300';
@endphp

<div {{ $attributes->class('ihsan-admin-stat-tile') }}>
    <div class="flex items-center gap-2">
        @if ($icon)
            <div @class(['flex size-7 items-center justify-center rounded-md', $toneClasses])>
                <x-dynamic-component :component="$icon" class="size-4" />
            </div>
        @endif

        <span @class([
            'text-xs font-semibold uppercase tracking-[0.14em]',
            'text-amber-700 dark:text-amber-300' => $tone === 'amber',
            'text-emerald-700 dark:text-emerald-300' => $tone === 'emerald',
            'text-red-700 dark:text-red-300' => $tone === 'red',
            'text-sky-700 dark:text-sky-300' => $tone === 'sky',
            'text-teal-700 dark:text-teal-300' => $tone === 'teal',
            'text-stone-700 dark:text-stone-300' => ! in_array($tone, ['amber', 'emerald', 'red', 'sky', 'teal'], true),
        ])>
            {{ $label }}
        </span>
    </div>

    <div class="mt-3 text-xl font-semibold text-ihsan-ink dark:text-white">{{ $value }}</div>
</div>
