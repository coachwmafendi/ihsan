{{-- resources/views/components/ui/copy-button.blade.php --}}
@props([
    'value' => '',
    'size' => 'md',
    'class' => '',
])

@php
    $sizeClasses = [
        'sm' => 'size-3.5',
        'md' => 'size-4',
        'lg' => 'size-5',
    ];
    $iconSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<button
    type="button"
    x-data="{ copied: false }"
    x-on:click="
        navigator.clipboard.writeText('{{ $value }}').then(() => {
            copied = true;
            setTimeout(() => copied = false, 2000);
        })
    "
    class="inline-flex items-center gap-1 text-gray-400 transition hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 {{ $class }}"
    title="Copy"
>
    <x-heroicon-o-clipboard-document
        x-show="!copied"
        class="{{ $iconSize }} shrink-0"
    />
    <x-heroicon-o-check
        x-show="copied"
        x-cloak
        class="{{ $iconSize }} shrink-0 text-green-600"
    />
</button>
