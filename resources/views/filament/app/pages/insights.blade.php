<x-filament-panels::page>
    <div class="space-y-6">
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
                @switch($activeTab)
                    @case('performance')
                        @include('filament.app.pages.insights-tabs.performance')
                    @case('recurring-plans')
                        @include('filament.app.pages.insights-tabs.recurring-plans')
                    @case('recurring-revenue')
                        @include('filament.app.pages.insights-tabs.recurring-revenue')
                    @case('retention')
                        @include('filament.app.pages.insights-tabs.retention')
                    @case('payment-methods')
                        @include('filament.app.pages.insights-tabs.payment-methods')
                    @case('frequencies')
                        @include('filament.app.pages.insights-tabs.frequencies')
                    @case('elements')
                        @include('filament.app.pages.insights-tabs.elements')
                    @case('url')
                        @include('filament.app.pages.insights-tabs.url')
                    @default
                        {{-- Overview --}}
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

                        <div class="mt-6 grid gap-6 xl:grid-cols-2">
                            <div>
                                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Status breakdown</h3>
                                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($statusBreakdown as $row)
                                        <div class="flex items-center justify-between py-3 text-sm" wire:key="status-{{ $row['label'] }}">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $row['label'] }}</span>
                                            <span class="font-medium text-gray-950 dark:text-white">{{ $row['value'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Recent donations</h3>
                                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($recentDonations as $donation)
                                        <div class="flex items-center justify-between gap-4 py-3" wire:key="recent-{{ $donation['donor'] }}-{{ $loop->index }}">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $donation['donor'] }}</div>
                                                <div class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $donation['campaign'] }} &middot; {{ $donation['type'] }}</div>
                                            </div>
                                            <div class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white">{{ $donation['amount'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                @endswitch
            </x-filament::section>

            <div class="space-y-3">
                @php
                    $tabs = [
                        'overview' => 'Overview',
                        'performance' => 'Performance',
                        'recurring-plans' => 'Recurring plans',
                        'recurring-revenue' => 'Recurring revenue',
                        'retention' => 'Retention',
                        'payment-methods' => 'Payment methods',
                        'frequencies' => 'Frequencies',
                        'elements' => 'Elements',
                        'url' => 'URL',
                    ];
                @endphp

                @foreach ($tabs as $key => $label)
                    <div
                        wire:click="setActiveTab('{{ $key }}')"
                        class="{{ $activeTab === $key ? 'border-primary-500 text-gray-950 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:border-gray-600 dark:hover:text-gray-300' }} cursor-pointer border-l-2 px-3 py-1.5 text-sm font-semibold transition-colors"
                    >
                        {{ $label }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
