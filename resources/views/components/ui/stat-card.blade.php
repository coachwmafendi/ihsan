{{-- resources/views/components/ui/stat-card.blade.php --}}
@props([
    'label'      => '',
    'value'      => '',
    'subtext'    => null,
    'trend'      => null,
    'trendColor' => 'gray',
    'progress'   => null,
    'valueClass' => 'text-3xl font-semibold tracking-tight text-gray-950 dark:text-white',
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
    <div @class(['mt-2', $valueClass])>{{ $value }}</div>
    @if (! is_null($progress))
        @php $clamped = max(0, min(100, (float) $progress)); $visible = $clamped > 0 ? max($clamped, 3) : 0; @endphp
        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
            <div class="h-full rounded-full bg-blue-500" style="width: {{ $visible }}%"></div>
        </div>
    @endif
    @if ($subtext)
        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $subtext }}</div>
    @endif
    @if ($trend)
        <div class="mt-2 text-sm font-medium {{ $trendClass }}">{{ $trend }}</div>
    @endif
</div>
