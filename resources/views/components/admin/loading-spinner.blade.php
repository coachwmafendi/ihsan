@props(['target'])

{{--
    One spinner for the admin panel's hand-rolled controls.

    Filament's own button component derives a loading indicator from its
    wire:click and needs nothing. These are the buttons and selects built by
    hand, where the slowest actions on the panel were also the silent ones.

    The delay matches Filament's: an action that returns quickly should not
    flash a spinner on its way past.
--}}
<span
    wire:loading.delay.default
    wire:target="{{ $target }}"
    data-loading-spinner
    class="inline-flex"
    role="status"
    aria-live="polite"
>
    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
    </svg>
    <span class="sr-only">Loading</span>
</span>
