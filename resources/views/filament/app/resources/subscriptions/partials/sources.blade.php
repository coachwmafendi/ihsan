@php
$record = $record ?? null;
if (! $record) return;
@endphp

<div class="px-6 py-4 space-y-3 text-sm">
    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Campaign</span>
        <span class="text-primary-600 dark:text-primary-400 font-medium">{{ $record->campaign?->title ?? '—' }}</span>
    </div>

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Element</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->element_label ?? '—' }}</span>
    </div>
</div>
