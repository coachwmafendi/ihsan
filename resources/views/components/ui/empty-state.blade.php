{{-- resources/views/components/ui/empty-state.blade.php --}}
@props([
    'icon' => 'heroicon-o-inbox',
    'title' => 'No items found',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
    'actionWireClick' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center']) }}>
    <div class="rounded-full bg-slate-50 p-4">
        <x-dynamic-component :component="$icon" class="size-8 text-slate-400" />
    </div>

    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1.5 max-w-xs text-sm text-slate-500">{{ $description }}</p>
    @endif

    @if ($actionLabel && ($actionUrl || $actionWireClick))
        <div class="mt-6">
            @if ($actionWireClick)
                <button type="button" wire:click="{{ $actionWireClick }}" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600">
                    <x-heroicon-o-plus class="size-4" />
                    {{ $actionLabel }}
                </button>
            @else
                <a href="{{ $actionUrl }}" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600">
                    <x-heroicon-o-plus class="size-4" />
                    {{ $actionLabel }}
                </a>
            @endif
        </div>
    @endif

    @if (isset($extra))
        <div class="mt-4">{{ $extra }}</div>
    @endif
</div>
