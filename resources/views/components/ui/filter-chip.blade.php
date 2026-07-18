{{-- resources/views/components/ui/filter-chip.blade.php --}}
@props([
    'label' => null,
    'value' => null,
    'clear' => null,
    'icon' => null,
])

<button
    type="button"
    {{ $attributes->class([
        'inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs transition-colors',
        'border-slate-300 bg-white text-slate-700' => filled($value),
        'border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800' => blank($value),
    ]) }}
>
    @if ($icon)
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-3 shrink-0" />
    @endif
    @if ($label)
        <span>{{ $label }}</span>
    @endif
    @if (filled($value))
        <span class="font-medium text-blue-600">{{ $value }}</span>
        @if ($clear)
            <span wire:click.stop="{{ $clear }}" class="ml-1 cursor-pointer border-l border-slate-200 pl-1.5 text-slate-500 hover:text-slate-800">
                <x-heroicon-o-x-mark class="size-3" />
            </span>
        @endif
    @endif
</button>
