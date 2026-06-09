{{-- resources/views/components/ui/section-header.blade.php --}}
@props([
    'title'    => '',
    'subtitle' => null,
])

<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($action))
        <div>{{ $action }}</div>
    @endif
</div>
