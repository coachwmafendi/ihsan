@props([
    'icon' => null,
    'label',
    'value',
    'note' => null,
])

<div {{ $attributes->class('ihsan-admin-metric-card') }}>
    <div class="ihsan-admin-metric-header">
        @if ($icon)
            <span class="ihsan-admin-metric-icon">
                <x-dynamic-component :component="$icon" class="size-4" />
            </span>
        @endif

        {{ $label }}
    </div>

    <div class="ihsan-admin-metric-value">{{ $value }}</div>

    @if ($note)
        <div class="ihsan-admin-metric-note">{{ $note }}</div>
    @endif
</div>
