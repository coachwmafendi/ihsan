{{-- resources/views/components/ui/copy-button.blade.php --}}
@props([
    'value' => '',
    'size' => 'md',
    'class' => '',
    'title' => 'Copy',
    'copiedText' => 'Copied',
])

@php
    $sizeClasses = [
        'sm' => 'size-3.5',
        'md' => 'size-4',
        'lg' => 'size-5',
    ];
    $iconSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<x-ui.tooltip :text="$title">
    <span class="relative inline-flex items-center" x-data="{ copied: false }">
        <button
            type="button"
            x-on:click="
                navigator.clipboard.writeText('{{ $value }}').then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                })
            "
            class="inline-flex items-center gap-1 text-slate-400 transition hover:text-slate-700 dark:text-gray-500 dark:hover:text-gray-300 {{ $class }}"
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

        <span
            x-show="copied"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-lg bg-white px-2 py-1 text-xs font-medium text-slate-700 shadow-[0_4px_20px_rgba(15,23,42,0.22)]"
        ><span aria-hidden="true" class="absolute top-full left-1/2 size-1.5 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-white"></span>{{ $copiedText }}</span>
    </span>
</x-ui.tooltip>
