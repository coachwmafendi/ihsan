{{-- resources/views/components/icon/donor-portal.blade.php --}}
@props(['class' => 'size-5'])

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="{{ $class }}">
    {{-- Doorway frame --}}
    <path d="M5 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5" />
    {{-- Inner vertical divider --}}
    <path d="M5 4v14a2 2 0 0 0 2 2" />
    {{-- Arrow entering the portal --}}
    <path d="M11 12H6" />
    <path d="m8 15-3-3 3-3" />
    {{-- Magic sparkle accent --}}
    <path d="M19.5 7v1.5M19.5 11V9.5M18 9.5h1.5M21 9.5h-1.5" />
</svg>
