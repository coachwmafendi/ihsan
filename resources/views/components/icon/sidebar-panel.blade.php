{{-- resources/views/components/icon/sidebar-panel.blade.php --}}
@props(['class' => 'size-5'])

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="{{ $class }}">
    {{-- Outer rounded panel --}}
    <path d="M5.25 4.5h13.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25H5.25a2.25 2.25 0 0 1-2.25-2.25V6.75a2.25 2.25 0 0 1 2.25-2.25Z" />
    {{-- Left sidebar divider --}}
    <path d="M9 4.5v15" />
</svg>
