{{-- resources/views/components/icon/donor-portal.blade.php --}}
@props(['class' => 'size-5'])

{{--
    A person inside a browser window: the page a donor signs into to see their
    own giving. Three strokes only — the previous drawing packed a doorway, an
    arrow and a sparkle into 20 pixels and they merged into a smudge.
--}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="{{ $class }}">
    {{-- Window frame with its title bar --}}
    <path d="M3 8.5V18a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2.5Zm0 0h18" />
    {{-- The donor: head and shoulders --}}
    <path d="M13.75 12.5a1.75 1.75 0 1 1-3.5 0 1.75 1.75 0 0 1 3.5 0Z" />
    <path d="M8.75 17.25a3.25 3.25 0 0 1 6.5 0" />
</svg>
