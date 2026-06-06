<x-filament-panels::page>
<div class="relative">
    {{-- Loading overlay for filter/search/pagination changes --}}
    <div wire:loading.delay.class="opacity-50" wire:target="tableFilters,tableSearch,tableSort,tableRecordsPerPage,previousTableSort,nextTableSort,previousTablePage,nextTablePage" class="transition-opacity duration-200">
        {{ $this->table }}
    </div>

    <div
        wire:loading.delay
        wire:target="tableFilters,tableSearch,tableSort,tableRecordsPerPage,previousTableSort,nextTableSort,previousTablePage,nextTablePage"
        class="absolute inset-0 z-10 flex items-center justify-center bg-white/30 dark:bg-gray-900/30 backdrop-blur-[1px]"
    >
        <div class="rounded-xl border border-gray-200 bg-white px-6 py-4 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <svg class="size-5 animate-spin text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Loading supporters...</span>
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
