{{-- resources/views/components/ui/progress-bar.blade.php --}}
@props([
    'percentage' => 0,
    'label' => null,
    'sublabel' => null,
    'size' => 'md',
    'color' => null,
    'showPercentage' => true,
])

@php
    $percentage = max(0, min(100, (int) $percentage));

    $sizeClasses = [
        'sm' => 'h-1.5',
        'md' => 'h-2.5',
        'lg' => 'h-4',
    ];
    $barSize = $sizeClasses[$size] ?? $sizeClasses['md'];

    if ($color) {
        $barColor = $color;
    } else {
        $barColor = $percentage >= 100 ? 'bg-emerald-500' : ($percentage >= 50 ? 'bg-teal-500' : 'bg-amber-500');
    }
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label || $sublabel || $showPercentage)
        <div class="flex items-center justify-between gap-2 mb-1.5">
            @if ($label)
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
            @endif
            <div class="flex items-center gap-1.5 ml-auto">
                @if ($showPercentage)
                    <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $percentage }}%</span>
                @endif
                @if ($sublabel)
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $sublabel }}</span>
                @endif
            </div>
        </div>
    @endif

    <div class="w-full overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
        <div
            class="rounded-full transition-all duration-500 ease-out {{ $barSize }} {{ $barColor }}"
            style="width: {{ $percentage }}%; {{ $percentage > 0 && $percentage < 4 ? 'min-width: 4px;' : '' }}"
        ></div>
    </div>
</div>
