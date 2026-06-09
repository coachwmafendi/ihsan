{{-- resources/views/components/ui/stat-card.blade.php --}}
@props([
    'label'      => '',
    'value'      => '',
    'subtext'    => null,
    'trend'      => null,
    'trendColor' => 'gray',
])

@php
    $trendColors = [
        'success' => 'text-green-600 dark:text-green-400',
        'danger'  => 'text-red-600 dark:text-red-400',
        'gray'    => 'text-gray-500 dark:text-gray-400',
    ];
    $trendClass = $trendColors[$trendColor] ?? $trendColors['gray'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10']) }}>
    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
    <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $value }}</div>
    @if ($subtext)
        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $subtext }}</div>
    @endif
    @if ($trend)
        <div class="mt-2 text-sm font-medium {{ $trendClass }}">{{ $trend }}</div>
    @endif
</div>
