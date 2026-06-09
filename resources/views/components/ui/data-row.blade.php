{{-- resources/views/components/ui/data-row.blade.php --}}
@props([
    'label' => '',
    'divider' => true,
])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4 py-3 text-sm' . ($divider ? '' : '')]) }}>
    <span class="min-w-0 truncate text-gray-500 dark:text-gray-400">{{ $label }}</span>
    <div class="shrink-0 text-right font-medium text-gray-950 dark:text-white">
        {{ $slot }}
    </div>
</div>
