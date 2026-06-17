{{-- resources/views/components/ui/alert.blade.php --}}
@props([
    'variant' => 'info',
    'icon' => null,
])

@php
$styles = match ($variant) {
    'success' => [
        'box' => 'bg-emerald-50 border-emerald-200',
        'text' => 'text-emerald-900',
        'icon' => 'text-emerald-600',
    ],
    'warning' => [
        'box' => 'bg-amber-50 border-amber-200',
        'text' => 'text-amber-900',
        'icon' => 'text-amber-600',
    ],
    'danger' => [
        'box' => 'bg-red-50 border-red-200',
        'text' => 'text-red-900',
        'icon' => 'text-red-600',
    ],
    default => [
        'box' => 'bg-blue-50 border-blue-200',
        'text' => 'text-blue-900',
        'icon' => 'text-blue-600',
    ],
};

$defaultIcon = match ($variant) {
    'success' => 'heroicon-o-check-circle',
    'warning' => 'heroicon-o-exclamation-triangle',
    'danger' => 'heroicon-o-x-circle',
    default => 'heroicon-o-information-circle',
};

$icon ??= $defaultIcon;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border px-4 py-4 '.$styles['box']]) }}>
    @if ($icon)
        <x-dynamic-component :component="$icon" class="size-5 shrink-0 mt-0.5 {{ $styles['icon'] }}" />
    @endif

    <div class="text-sm {{ $styles['text'] }}">
        {!! $slot !!}
    </div>
</div>
