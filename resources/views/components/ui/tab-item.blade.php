{{-- resources/views/components/ui/tab-item.blade.php --}}
@props([
    'active' => false,
])

@php
    $class = $active
        ? 'border-primary-500 text-gray-950 dark:text-white'
        : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:border-gray-600 dark:hover:text-gray-300';
@endphp

<div {{ $attributes->merge(['class' => 'cursor-pointer border-l-2 px-3 py-1.5 text-sm font-semibold transition-colors ' . $class]) }}>
    {{ $slot }}
</div>
