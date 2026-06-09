{{-- resources/views/components/ui/page-header.blade.php --}}
@props([
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
            @endif
        </div>
        @if (isset($actions))
            <div class="flex shrink-0 items-center gap-3">{{ $actions }}</div>
        @endif
    </div>
</div>
