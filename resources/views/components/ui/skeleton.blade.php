{{-- resources/views/components/ui/skeleton.blade.php --}}
@props([
    'height'  => '1rem',
    'width'   => '100%',
    'rounded' => '0.375rem',
])

<div
    {{ $attributes->merge(['class' => 'animate-pulse bg-gray-100 dark:bg-gray-800']) }}
    style="height: {{ $height }}; width: {{ $width }}; border-radius: {{ $rounded }};"
></div>
