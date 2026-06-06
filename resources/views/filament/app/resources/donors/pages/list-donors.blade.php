<x-filament-panels::page x-data="{ tableLoaded: false }" x-init="$nextTick(() => setTimeout(() => tableLoaded = true, 300))">
    <div
        class="space-y-4"
        x-show="!tableLoaded"
        x-transition.opacity.duration.200ms
        x-cloak
        aria-hidden="true"
    >
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <div class="flex items-center gap-4 bg-gray-50 px-4 py-3 dark:bg-gray-800">
                    <div class="h-4 w-32 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 w-48 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 w-32 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 w-28 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-4 w-28 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                </div>

                @for ($i = 0; $i < 8; $i++)
                    <div class="flex items-center gap-4 px-4 py-4">
                        <div class="h-4 w-32 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                        <div class="h-4 w-48 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                        <div class="h-4 w-24 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                        <div class="h-4 w-20 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                        <div class="h-4 w-20 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                    </div>
                @endfor
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="h-8 w-32 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
            <div class="h-8 w-48 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
        </div>
    </div>

    <div x-show="tableLoaded" x-transition.opacity.duration.200ms>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
