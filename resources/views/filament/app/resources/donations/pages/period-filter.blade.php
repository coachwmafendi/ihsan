<div x-data="{ open: false }" class="relative">
    <span
        @click="open = !open"
        @click.away="open = false"
        class="cursor-pointer rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
    >
        Period: {{ $this->getDateRangeLabel() }}
    </span>
    <div
        x-show="open"
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
