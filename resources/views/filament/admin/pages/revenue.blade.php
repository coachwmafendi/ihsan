<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">
                    Platform Revenue
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Overview of donation volume, processing fees, and organizational performance.
                </p>
            </div>

            {{-- Period Filter --}}
            <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">
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
                            'relative px-3.5 py-2 text-xs font-semibold rounded-lg transition-all duration-200',
                            'bg-primary-600 text-white shadow-sm' => $period === $key,
                            'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700' => $period !== $key,
                        ])
                    >
                        {{ $label }}
                        @if ($period === $key)
                            <span class="absolute -bottom-px left-2 right-2 h-0.5 rounded-full bg-white/30"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Summary Cards Row 1 --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-filament::section class="relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 h-20 w-20 rounded-full bg-primary-50 opacity-50 dark:bg-primary-900/20"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-banknotes class="h-4 w-4 text-primary-500" />
                        Total processing fees
                    </div>
                    <div class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        MYR {{ $totalProcessingFees }}
                    </div>
                    <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $totalTransactions }} successful transaction{{ $totalTransactions !== 1 ? 's' : '' }}
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 h-20 w-20 rounded-full bg-success-50 opacity-50 dark:bg-success-900/20"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-success-500" />
                        Donation volume
                    </div>
                    <div class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        MYR {{ $totalDonationVolume }}
                    </div>
                    <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Gross from succeeded donations
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 h-20 w-20 rounded-full bg-info-50 opacity-50 dark:bg-info-900/20"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-calculator class="h-4 w-4 text-info-500" />
                        Avg donation
                    </div>
                    <div class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        MYR {{ $averageDonationSize }}
                    </div>
                    <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Average per transaction
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 h-20 w-20 rounded-full bg-warning-50 opacity-50 dark:bg-warning-900/20"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-receipt-percent class="h-4 w-4 text-warning-500" />
                        Avg fee / txn
                    </div>
                    <div class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        MYR {{ $averageFeePerTransaction }}
                    </div>
                    <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $nominalFeeRate }}% nominal rate
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section class="relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 h-20 w-20 rounded-full bg-danger-50 opacity-50 dark:bg-danger-900/20"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-chart-pie class="h-4 w-4 text-danger-500" />
                        Effective rate
                    </div>
                    <div class="mt-2 text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ $effectiveFeeRate }}%
                    </div>
                    <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Fees &divide; volume
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Fee Breakdown --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-presentation-chart-line class="h-5 w-5 text-gray-400" />
                    Fee Collection Breakdown
                </div>
            </x-slot>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-lg border border-success-200 bg-success-50/50 p-4 dark:border-success-800 dark:bg-success-900/10">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-success-100 dark:bg-success-800">
                            <x-heroicon-o-check-circle class="h-4 w-4 text-success-600 dark:text-success-400" />
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-success-700 dark:text-success-400">Paid</span>
                    </div>
                    <div class="mt-3 text-xl font-bold text-gray-950 dark:text-white">
                        MYR {{ $paidFees }}
                    </div>
                </div>

                <div class="rounded-lg border border-teal-200 bg-teal-50/50 p-4 dark:border-teal-800 dark:bg-teal-900/10">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-800">
                            <x-heroicon-o-arrow-path class="h-4 w-4 text-teal-600 dark:text-teal-400" />
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-teal-700 dark:text-teal-400">Collected</span>
                    </div>
                    <div class="mt-3 text-xl font-bold text-gray-950 dark:text-white">
                        MYR {{ $collectedFees }}
                    </div>
                </div>

                <div class="rounded-lg border border-warning-200 bg-warning-50/50 p-4 dark:border-warning-800 dark:bg-warning-900/10">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-800">
                            <x-heroicon-o-clock class="h-4 w-4 text-warning-600 dark:text-warning-400" />
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-warning-700 dark:text-warning-400">Pending</span>
                    </div>
                    <div class="mt-3 text-xl font-bold text-gray-950 dark:text-white">
                        MYR {{ $pendingFees }}
                    </div>
                </div>

                <div class="rounded-lg border border-info-200 bg-info-50/50 p-4 dark:border-info-800 dark:bg-info-900/10">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-info-100 dark:bg-info-800">
                            <x-heroicon-o-document-text class="h-4 w-4 text-info-600 dark:text-info-400" />
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-info-700 dark:text-info-400">Invoiced</span>
                    </div>
                    <div class="mt-3 text-xl font-bold text-gray-950 dark:text-white">
                        MYR {{ $invoicedFees }}
                    </div>
                </div>

                <div class="rounded-lg border border-danger-200 bg-danger-50/50 p-4 dark:border-danger-800 dark:bg-danger-900/10">
                    <div class="flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-800">
                            <x-heroicon-o-x-circle class="h-4 w-4 text-danger-600 dark:text-danger-400" />
                        </div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-danger-700 dark:text-danger-400">Failed</span>
                    </div>
                    <div class="mt-3 text-xl font-bold text-gray-950 dark:text-white">
                        MYR {{ $failedFees }}
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Revenue by Organization --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-building-office-2 class="h-5 w-5 text-gray-400" />
                    Revenue by Organization
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <th class="pb-3 pr-4">Organization</th>
                            <th class="pb-3 pr-4 text-right">Donations</th>
                            <th class="pb-3 pr-4 text-right">Volume</th>
                            <th class="pb-3 pr-4 text-right">Avg donation</th>
                            <th class="pb-3 pr-4 text-right">Processing fees</th>
                            <th class="pb-3 text-right">Eff. rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($revenueByOrganization as $row)
                            <tr class="group text-sm transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-3.5 pr-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            {{ strtoupper(substr($row['name'], 0, 1)) }}
                                        </div>
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 pr-4 text-right tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ number_format($row['donations']) }}
                                </td>
                                <td class="py-3.5 pr-4 text-right tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $row['volume'] }}
                                </td>
                                <td class="py-3.5 pr-4 text-right tabular-nums text-gray-600 dark:text-gray-400">
                                    {{ $row['avg_donation'] }}
                                </td>
                                <td class="py-3.5 pr-4 text-right tabular-nums font-semibold text-gray-950 dark:text-white">
                                    {{ $row['fees'] }}
                                </td>
                                <td class="py-3.5 text-right">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold',
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
                                    <div class="flex flex-col items-center gap-2 text-gray-400 dark:text-gray-500">
                                        <x-heroicon-o-chart-bar class="h-8 w-8" />
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
