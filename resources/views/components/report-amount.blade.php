@props([
    'amount',
    'isApproximate' => false,
    'tooltip' => null,
    'class' => 'text-sm font-semibold text-slate-900',
])

<span
    class="{{ $class }}"
    @if ($isApproximate && $tooltip)
        title="{{ $tooltip }}"
    @endif
>
    @if ($isApproximate)
        ≈
    @endif
    MYR {{ number_format((float) $amount, 2) }}
</span>
