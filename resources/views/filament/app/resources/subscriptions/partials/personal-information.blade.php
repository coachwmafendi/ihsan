@php
$record = $record ?? null;
if (! $record) return;
@endphp

<div class="px-6 py-4 space-y-3 text-sm">
    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Name</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->donor?->name ?? '—' }}</span>
    </div>

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Email</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->donor?->email ?? '—' }}</span>
    </div>

    <div class="grid items-baseline gap-x-6" style="grid-template-columns: 240px 1fr;">
        <span class="text-gray-500 dark:text-gray-400">Phone</span>
        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->donor?->phone ?? '—' }}</span>
    </div>
</div>
