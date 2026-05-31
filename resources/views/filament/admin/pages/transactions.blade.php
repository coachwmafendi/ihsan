<x-filament-panels::page>
    <div class="ihsan-admin-page">
        <div class="mb-3 flex items-center gap-2" x-data="{ open: false }">
            <span class="text-xs font-medium text-ihsan-muted dark:text-stone-400">Period:</span>
            <div class="relative">
                <span
                    @click="open = !open"
                    @click.away="open = false"
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-ihsan-cream px-3 py-1 text-xs font-medium text-ihsan-muted ring-1 ring-stone-200 transition-colors hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-400 dark:ring-stone-700 dark:hover:bg-stone-700"
                >
                    <x-dynamic-component component="heroicon-o-calendar-days" class="size-3.5" />
                    {{ $this->activePeriodLabel }}
                    <x-dynamic-component component="heroicon-o-chevron-down" class="size-3" />
                </span>
                <div
                    x-show="open"
                    x-transition
                    class="absolute left-0 top-full z-50 mt-1 w-44 overflow-hidden rounded-lg border border-stone-200 bg-white shadow-lg dark:border-stone-700 dark:bg-stone-900"
                    style="display: none;"
                >
                    @foreach ([
                        'all' => 'All Time',
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        '7_days' => 'Last 7 days',
                        '14_days' => 'Last 14 days',
                        '30_days' => 'Last 30 days',
                        'this_month' => 'This month',
                        'last_month' => 'Last month',
                    ] as $value => $label)
                        <button
                            wire:click="setDateRange('{{ $value }}')"
                            @click="open = false"
                            class="{{ $this->dateRange === $value ? 'bg-stone-100 text-stone-900 dark:bg-stone-800 dark:text-white' : 'text-stone-600 hover:bg-stone-50 dark:text-stone-400 dark:hover:bg-stone-800' }} flex w-full items-center gap-2 px-4 py-2 text-left text-sm transition-colors"
                        >
                            @if ($this->dateRange === $value)
                                <x-dynamic-component component="heroicon-o-check" class="size-3.5 shrink-0" />
                            @else
                                <span class="size-3.5 shrink-0"></span>
                            @endif
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-3">
            <x-admin.metric-card
                icon="heroicon-o-banknotes"
                label="Total Amount"
                :value="'MYR '.number_format($this->totals['amount'], 2)"
            />
            <x-admin.metric-card
                icon="heroicon-o-receipt-percent"
                label="Total Fee"
                :value="'MYR '.number_format($this->totals['fee'], 2)"
            />
            <x-admin.metric-card
                icon="heroicon-o-arrow-trending-down"
                label="Total Org Receives"
                :value="'MYR '.number_format($this->totals['org_receives'], 2)"
            />
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
