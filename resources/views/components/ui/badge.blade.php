@props([
    'status' => 'default',
    'color' => null,
    'size' => 'md',
])

@php
$status = strtolower((string) $status);

$colorMap = [
    'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'live' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'succeeded' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'draft' => 'bg-slate-50 text-slate-700 ring-slate-500/10',
    'paused' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'ended' => 'bg-red-50 text-red-700 ring-red-600/10',
    'danger' => 'bg-red-50 text-red-700 ring-red-600/10',
    'failed' => 'bg-red-50 text-red-700 ring-red-600/10',
    'refunded' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'pending' => 'bg-slate-50 text-slate-700 ring-slate-500/10',
    'cancelled' => 'bg-red-50 text-red-700 ring-red-600/10',
    'past_due' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'incomplete' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'incomplete_expired' => 'bg-slate-50 text-slate-700 ring-slate-500/10',
    'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'blocked' => 'bg-red-50 text-red-700 ring-red-600/10',
    'flagged' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'clean' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'info' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
    'blue' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
    'default' => 'bg-slate-50 text-slate-700 ring-slate-500/10',
];

$sizeMap = [
    'xs' => 'px-1.5 py-0.5 text-[10px] uppercase tracking-wide',
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-1 text-xs',
    'lg' => 'px-3 py-1.5 text-sm',
];

$classes = $color ?? ($colorMap[$status] ?? $colorMap['default']);
$sizeClasses = $sizeMap[$size] ?? $sizeMap['md'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md font-medium ring-1 ring-inset ' . $classes . ' ' . $sizeClasses]) }}>
    {{ $slot }}
</span>
