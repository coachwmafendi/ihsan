{{-- resources/views/components/ui/detail-row.blade.php --}}
@props([
    'label' => '',
    'value' => null,
    'copyable' => false,
    'href' => null,
    'icon' => null,
    'labelWidth' => '180px',
])

<div {{ $attributes->merge(['class' => 'flex items-baseline gap-8 py-1.5 text-sm']) }}>
    <span class="shrink-0 text-gray-500 dark:text-gray-400" style="width: {{ $labelWidth }}">{{ $label }}</span>
    <div class="flex items-center gap-1.5 min-w-0">
        @if ($icon)
            <x-dynamic-component :component="$icon" class="size-4 shrink-0 text-gray-400 dark:text-gray-500" />
        @endif

        @if ($href)
            <a
                href="{{ $href }}"
                class="truncate text-gray-900 transition hover:text-primary-600 dark:text-gray-100 dark:hover:text-primary-400"
            >
                {{ $value ?? $slot }}
            </a>
        @else
            <span class="truncate text-gray-900 dark:text-gray-100">
                {{ $value ?? $slot }}
            </span>
        @endif

        @if ($copyable && $value)
            <x-ui.copy-button :value="$value" size="sm" />
        @endif
    </div>
</div>
