@props([
    'amount',
    'isApproximate' => false,
    'tooltip' => null,
    'class' => 'text-sm font-semibold text-slate-900',
])

@if ($isApproximate && filled($tooltip))
    <x-ui.tooltip :text="$tooltip">
        <span class="{{ $class }}">
            ≈ MYR {{ number_format((float) $amount, 2) }}
        </span>
    </x-ui.tooltip>
@else
    <span class="{{ $class }}">
        @if ($isApproximate)
            ≈
        @endif
        MYR {{ number_format((float) $amount, 2) }}
    </span>
@endif
