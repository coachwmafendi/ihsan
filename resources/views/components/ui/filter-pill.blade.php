{{-- resources/views/components/ui/filter-pill.blade.php --}}
@props([
    'active' => false,
    'static' => false,
    'tag'    => null,
])

@php
    $base = 'inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200';
    $interactive = 'cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-gray-800';
    $activeClass = $active ? 'border-primary-500 text-primary-700 bg-primary-50 dark:bg-primary-900/20 dark:text-primary-400' : 'text-gray-700';
    $classes = $base . ($static ? ' ' . $activeClass : ' ' . $interactive . ' ' . $activeClass);
    // Use <button> when wire:click or @click is present so Livewire/Alpine can bind correctly.
    // Use <div> for static display pills.
    $element = $static ? 'div' : 'button';
    $type = $element === 'button' ? 'type="button"' : '';
@endphp

<{{ $element }} {{ $type }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $element }}>
