@props([
    'sidebar' => false,
])

@php
    $wordmarkClasses = '[&>div:last-child]:text-teal-700 dark:[&>div:last-child]:text-teal-400 [&>div:last-child]:font-semibold [&>div:last-child]:tracking-tight';
@endphp

@if($sidebar)
    <flux:sidebar.brand name="ihsan" class="{{ $wordmarkClasses }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md">
            <x-app-logo-icon class="h-7 w-auto" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="ihsan" class="{{ $wordmarkClasses }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md">
            <x-app-logo-icon class="h-7 w-auto" />
        </x-slot>
    </flux:brand>
@endif
