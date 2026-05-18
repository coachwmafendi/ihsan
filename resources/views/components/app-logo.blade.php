@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Ihsan" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md">
            <img src="{{ asset('logo-ihsan.png') }}" alt="Ihsan" class="h-7 w-auto" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Ihsan" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md">
            <img src="{{ asset('logo-ihsan.png') }}" alt="Ihsan" class="h-7 w-auto" />
        </x-slot>
    </flux:brand>
@endif
