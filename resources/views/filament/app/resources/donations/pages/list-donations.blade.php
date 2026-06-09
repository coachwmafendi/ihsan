<x-filament-panels::page>
<div class="space-y-4">
    {{-- Donations Table or Empty State --}}
    @if ($this->hasDonations())
        <div class="space-y-4">
            {{-- Period Filter --}}
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.filter-pill @click="$refs.periodDropdown.__x.$data.open = ! $refs.periodDropdown.__x.$data.open">
                    Period: {{ $this->getDateRangeLabel() }}
                </x-ui.filter-pill>

                {{-- Hidden dropdown trigger managed by Alpine --}}
                <div x-data="{ open: false }" x-ref="periodDropdown" class="relative">
                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-transition
                        class="absolute left-0 top-full z-50 mt-1 w-48 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                        style="display: none;"
                    >
                        @foreach (['all' => 'All Time', 'today' => 'Today', 'yesterday' => 'Yesterday', '7_days' => 'Last 7 days', '30_days' => 'Last 30 days', '90_days' => 'Last 90 days', 'this_month' => 'This month'] as $value => $label)
                            <button
                                wire:click="setDateRange('{{ $value }}')"
                                @click="open = false"
                                class="{{ $this->dateRange === $value ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' }} flex w-full items-center px-4 py-2 text-left text-sm transition-colors"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="relative">
                <div wire:loading.delay.class="opacity-50" wire:target="tableFilters,tableSearch,tableSort,tableRecordsPerPage,previousTableSort,nextTableSort,previousTablePage,nextTablePage,setDateRange" class="transition-opacity duration-200">
                    {{ $this->table }}
                </div>

                <div
                    wire:loading.delay
                    wire:target="tableFilters,tableSearch,tableSort,tableRecordsPerPage,previousTableSort,nextTableSort,previousTablePage,nextTablePage,setDateRange"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/30 dark:bg-gray-900/30 backdrop-blur-[1px]"
                >
                    <div class="rounded-xl border border-gray-200 bg-white px-6 py-4 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-3">
                            <svg class="size-5 animate-spin text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Loading donations...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <x-ui.empty-state
            icon="heroicon-o-banknotes"
            title="No donations yet"
            description="Donations will appear here once supporters start giving to your campaigns."
        />
    @endif
</div>
</x-filament-panels::page>
