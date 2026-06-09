{{-- resources/views/components/ui/empty-state.blade.php --}}
@props([
    'icon' => 'heroicon-o-inbox',
    'title' => 'No items found',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
    'actionWireClick' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900']) }}>
    <div class="rounded-full bg-gray-50 p-4 dark:bg-gray-800">
        <x-dynamic-component :component="$icon" class="size-8 text-gray-400 dark:text-gray-500" />
    </div>

    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1.5 max-w-xs text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @if ($actionLabel && ($actionUrl || $actionWireClick))
        <div class="mt-6">
            @if ($actionWireClick)
                <button
                    type="button"
                    wire:click="{{ $actionWireClick }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:bg-primary-500 dark:hover:bg-primary-400"
                >
                    <x-heroicon-o-plus class="size-4" />
                    {{ $actionLabel }}
                </button>
            @else
                <a
                    href="{{ $actionUrl }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 dark:bg-primary-500 dark:hover:bg-primary-400"
                >
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
