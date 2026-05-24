<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Frequencies</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">One-time vs recurring donation breakdown</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @foreach ($frequencyBreakdown as $row)
            <x-filament::section wire:key="freq-{{ $row['label'] }}">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $row['label'] }}</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $row['value'] }}</div>
            </x-filament::section>
        @endforeach
    </div>
</div>
