@props([
    'title',
])

<div {{ $attributes->class('rounded-lg border border-stone-200 bg-white/70 p-5 dark:border-stone-800 dark:bg-stone-900/70') }}>
    <h3 class="text-sm font-semibold text-ihsan-ink dark:text-white">{{ $title }}</h3>
    <p class="mt-1 text-sm text-ihsan-muted dark:text-stone-400">
        {{ $slot }}
    </p>
</div>
