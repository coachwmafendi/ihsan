{{-- resources/views/components/ui/select.blade.php --}}
@props(['variant' => 'default'])

@php
$baseClass = 'rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500';

$variantClass = match ($variant) {
    'auth' => 'px-4 py-2.5 focus:ring-2 focus:ring-teal-500 focus:border-transparent',
    default => 'px-3 py-2 focus:border-teal-500 focus:ring-1 focus:ring-teal-500',
};
@endphp

<flux:select
    {{ $attributes->merge([
        'class' => $baseClass.' '.$variantClass,
    ]) }}
>
    {{ $slot }}
</flux:select>
