@props([
    'label' => 'View donations for this period',
])

<a
    {{ $attributes }}
    wire:navigate
    class="rounded-lg border border-slate-200 p-2 text-slate-400 hover:border-slate-300 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-teal-500"
    aria-label="{{ $label }}"
>
    <x-heroicon-o-arrow-top-right-on-square class="size-4" />
</a>
