<x-filament-panels::page>
    <div class="ihsan-admin-page">
        <div class="ihsan-admin-toolbar">
            <div>
                <div class="ihsan-admin-kicker">Financial operations</div>
                <h2 class="ihsan-admin-title">Platform Revenue</h2>
                <p class="ihsan-admin-description">
                    Overview of donation volume, processing fees, and organizational performance.
                </p>
            </div>

            <div class="ihsan-admin-segmented">
                @php
                    $periods = [
                        'today' => 'Today',
                        'this_week' => 'This Week',
                        'this_month' => 'This Month',
                        'this_year' => 'This Year',
                        'all_time' => 'All Time',
                    ];
                @endphp
                @foreach ($periods as $key => $label)
                    <button
                        wire:click="$set('period', '{{ $key }}')"
                        wire:loading.attr="disabled"
                        type="button"
                        @class([
                            'ihsan-admin-segmented-button',
                            'ihsan-admin-segmented-button-active' => $period === $key,
                            'ihsan-admin-segmented-button-idle' => $period !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-admin.metric-card icon="heroicon-o-banknotes" label="Total processing fees" :value="'MYR '.$totalProcessingFees" :note="$totalTransactions.' successful transaction'.($totalTransactions !== 1 ? 's' : '')" />
            <x-admin.metric-card icon="heroicon-o-arrow-trending-up" label="Donation volume" :value="'MYR '.$totalDonationVolume" note="Gross from succeeded donations" />
            <x-admin.metric-card icon="heroicon-o-calculator" label="Avg donation" :value="'MYR '.$averageDonationSize" note="Average per transaction" />
            <x-admin.metric-card icon="heroicon-o-receipt-percent" label="Avg fee / txn" :value="'MYR '.$averageFeePerTransaction" :note="$nominalFeeRate.'% nominal rate'" />
            <x-admin.metric-card icon="heroicon-o-chart-pie" label="Effective rate" :value="$effectiveFeeRate.'%'" note="Fees / volume" />
        </div>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-presentation-chart-line class="size-5 text-ihsan-terracotta dark:text-orange-300" />
                    Fee Collection Breakdown
                </div>
            </x-slot>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-admin.stat-tile icon="heroicon-o-check-circle" label="Paid" :value="'MYR '.$paidFees" tone="emerald" />
                <x-admin.stat-tile icon="heroicon-o-arrow-path" label="Collected" :value="'MYR '.$collectedFees" tone="teal" />
                <x-admin.stat-tile icon="heroicon-o-clock" label="Pending" :value="'MYR '.$pendingFees" tone="amber" />
                <x-admin.stat-tile icon="heroicon-o-document-text" label="Invoiced" :value="'MYR '.$invoicedFees" tone="sky" />
                <x-admin.stat-tile icon="heroicon-o-x-circle" label="Failed" :value="'MYR '.$failedFees" tone="red" />
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-building-office-2 class="size-5 text-ihsan-terracotta dark:text-orange-300" />
                    Revenue by Organization
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="ihsan-admin-table">
                    <thead>
                        <tr>
                            <th>Organization</th>
                            <th class="text-right">Donations</th>
                            <th class="text-right">Volume</th>
                            <th class="text-right">Avg donation</th>
                            <th class="text-right">Processing fees</th>
                            <th class="text-right">Eff. rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($revenueByOrganization as $row)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="flex size-8 shrink-0 items-center justify-center rounded-md bg-stone-100 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:ring-stone-700">
                                            {{ strtoupper(substr($row['name'], 0, 1)) }}
                                        </div>
                                        <span class="font-medium text-ihsan-ink dark:text-white">{{ $row['name'] }}</span>
                                    </div>
                                </td>
                                <td class="text-right tabular-nums text-ihsan-muted dark:text-stone-400">
                                    {{ number_format($row['donations']) }}
                                </td>
                                <td class="text-right tabular-nums text-ihsan-muted dark:text-stone-400">
                                    {{ $row['volume'] }}
                                </td>
                                <td class="text-right tabular-nums text-ihsan-muted dark:text-stone-400">
                                    {{ $row['avg_donation'] }}
                                </td>
                                <td class="text-right tabular-nums font-semibold text-ihsan-ink dark:text-white">
                                    {{ $row['fees'] }}
                                </td>
                                <td class="text-right">
                                    <span @class([
                                        'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold',
                                        'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' => (float) str_replace('%', '', $row['effective_rate']) <= 3,
                                        'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400' => (float) str_replace('%', '', $row['effective_rate']) > 3 && (float) str_replace('%', '', $row['effective_rate']) <= 5,
                                        'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400' => (float) str_replace('%', '', $row['effective_rate']) > 5,
                                    ])>
                                        {{ $row['effective_rate'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center">
                                    <div class="flex flex-col items-center gap-2 text-ihsan-muted dark:text-stone-500">
                                        <x-heroicon-o-chart-bar class="size-8" />
                                        <span class="text-sm font-medium">No revenue data for this period.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
