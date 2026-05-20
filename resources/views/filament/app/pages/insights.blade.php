<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-200">Langkah Setup Organisasi</h2>
                    <ol class="mt-3 flex flex-wrap gap-x-8 gap-y-2 text-sm text-amber-800 dark:text-amber-300">
                        <li class="flex items-center gap-1.5">
                            @if ($hasCampaigns)
                                <x-heroicon-s-check-circle class="size-4 text-emerald-600" />
                            @else
                                <span class="flex size-4 items-center justify-center rounded-full bg-amber-200 text-[10px] font-bold text-amber-800 dark:bg-amber-700 dark:text-amber-200">1</span>
                            @endif
                            Cipta kempen
                        </li>
                        <li class="flex items-center gap-1.5">
                            @if ($hasElements)
                                <x-heroicon-s-check-circle class="size-4 text-emerald-600" />
                            @else
                                <span class="flex size-4 items-center justify-center rounded-full bg-amber-200 text-[10px] font-bold text-amber-800 dark:bg-amber-700 dark:text-amber-200">2</span>
                            @endif
                            Pasang elemen derma
                        </li>
                        <li class="flex items-center gap-1.5">
                            @if ($hasDonations)
                                <x-heroicon-s-check-circle class="size-4 text-emerald-600" />
                            @else
                                <span class="flex size-4 items-center justify-center rounded-full bg-amber-200 text-[10px] font-bold text-amber-800 dark:bg-amber-700 dark:text-amber-200">3</span>
                            @endif
                            Mula terima derma
                        </li>
                    </ol>
                </div>
                <p class="shrink-0 text-xs text-amber-600 dark:text-amber-400">Profil & pembayaran diuruskan oleh Pentadbir</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Date Last 7 days
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Aggregation Daily
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Campaign All
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Source Direct + UTM
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Frequency All
            </span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total raised</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalRaised }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $successfulDonationsCount }} successful donations</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Recurring revenue</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $monthlyRecurringRevenue }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $activeRecurringDonors }} active recurring donors</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">One-time donations</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $oneTimeDonationsTotal }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Average MYR {{ $averageDonationAmount }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">First installments</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $firstInstallmentsTotal }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $successRate }}% payment success rate</div>
            </x-filament::section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <x-filament::section>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Overview</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Performance over the last 7 days</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                        Metric Revenue
                    </div>
                </div>

                <div class="mt-8 flex h-64 items-end gap-3 border-b border-gray-200 pb-2 dark:border-gray-700">
                    @foreach ($dailyRevenue as $point)
                        <div class="flex h-full min-w-0 flex-1 flex-col justify-end gap-2" wire:key="revenue-{{ $point['label'] }}">
                            <div
                                class="rounded-t-md bg-primary-500/80"
                                style="height: {{ $point['height'] }}%"
                                title="{{ $point['label'] }}: MYR {{ $point['amount'] }}"
                            ></div>
                            <div class="truncate text-center text-xs text-gray-500 dark:text-gray-400">{{ $point['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <div class="space-y-3">
                @foreach (['Overview', 'Performance', 'Recurring plans', 'Recurring revenue', 'Retention', 'Payment methods', 'Frequencies', 'Elements', 'URL', 'UTM'] as $section)
                    <div
                        class="{{ $loop->first ? 'border-primary-500 text-gray-950 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400' }} border-l-2 px-3 py-1.5 text-sm font-semibold"
                    >
                        {{ $section }}
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-filament::section>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Payment methods</h3>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Card / Wallet</span>
                        <span class="font-medium text-gray-950 dark:text-white">100%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-full rounded-full bg-primary-500" style="width: 100%"></div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Frequencies</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($frequencyBreakdown as $row)
                        <div class="flex items-center justify-between text-sm" wire:key="frequency-{{ $row['label'] }}">
                            <span class="text-gray-500 dark:text-gray-400">{{ $row['label'] }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Recurring plans</h3>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $activeSubscriptionsCount }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Active</div>
                    </div>
                    <div>
                        <div class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $pastDueSubscriptionsCount }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Past due</div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <x-filament::section>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Status breakdown</h3>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($statusBreakdown as $row)
                        <div class="flex items-center justify-between py-3 text-sm" wire:key="status-{{ $row['label'] }}">
                            <span class="text-gray-500 dark:text-gray-400">{{ $row['label'] }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Recent donations</h3>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($recentDonations as $donation)
                        <div class="flex items-center justify-between gap-4 py-3" wire:key="recent-{{ $donation['donor'] }}-{{ $loop->index }}">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $donation['donor'] }}</div>
                                <div class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $donation['campaign'] }} · {{ $donation['type'] }}</div>
                            </div>
                            <div class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white">{{ $donation['amount'] }}</div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
