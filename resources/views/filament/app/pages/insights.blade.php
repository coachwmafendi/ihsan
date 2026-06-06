<x-filament-panels::page>
<div x-data="{ loaded: false }" x-init="$nextTick(() => setTimeout(() => loaded = true, 300))">

    {{-- Skeleton --}}
    <div
        class="space-y-6"
        x-show="!loaded"
        x-transition.opacity.duration.200ms
        x-cloak
        aria-hidden="true"
    >
        {{-- Filter bar skeleton --}}
        <div class="flex flex-wrap items-center gap-2">
            <div class="h-9 w-32 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
            <div class="h-9 w-28 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
            <div class="h-9 w-36 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
            <div class="h-9 w-32 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
            <div class="h-9 w-28 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
        </div>

        {{-- KPI cards skeleton --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @for ($i = 0; $i < 4; $i++)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="h-4 w-24 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                    <div class="mt-3 h-8 w-36 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                    <div class="mt-3 h-4 w-40 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                </div>
            @endfor
        </div>

        {{-- Main + sidebar skeleton --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="h-5 w-24 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="mt-2 h-4 w-40 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                    </div>
                    <div class="h-9 w-28 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                </div>

                <div class="mt-8 flex h-64 items-end gap-3 border-b border-gray-200 pb-2 dark:border-gray-700">
                    @for ($i = 0; $i < 7; $i++)
                        <div class="flex h-full min-w-0 flex-1 flex-col justify-end gap-2">
                            <div class="rounded-t-md bg-gray-200 animate-pulse dark:bg-gray-700" style="height: {{ rand(20, 90) }}%"></div>
                            <div class="h-3 w-full animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                        </div>
                    @endfor
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-2">
                    <div>
                        <div class="h-5 w-32 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="mt-4 space-y-3">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="flex items-center justify-between py-2">
                                    <div class="h-4 w-24 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="h-4 w-16 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                                </div>
                            @endfor
                        </div>
                    </div>
                    <div>
                        <div class="h-5 w-32 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="mt-4 space-y-3">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="flex items-center justify-between py-2">
                                    <div class="h-4 w-32 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="h-4 w-16 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @for ($i = 0; $i < 9; $i++)
                    <div class="h-7 w-full animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Actual Content --}}
    <div
        class="space-y-6"
        x-show="loaded"
        x-transition.opacity.duration.200ms
    >
        <div class="flex flex-wrap items-center gap-2">
            <div x-data="{ open: false }" class="relative">
                <span
                    @click="open = !open"
                    @click.away="open = false"
                    class="cursor-pointer rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Date @switch($dateRange)
                        @case('7_days') Last 7 days @break
                        @case('30_days') Last 30 days @break
                        @case('90_days') Last 90 days @break
                        @case('this_month') This month @break
                        @default All Time
                    @endswitch
                </span>
                <div
                    x-show="open"
                    x-transition
                    class="absolute left-0 top-full z-50 mt-1 w-48 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    style="display: none;"
                >
                    @foreach (['7_days' => 'Last 7 days', '30_days' => 'Last 30 days', '90_days' => 'Last 90 days', 'this_month' => 'This month', 'all' => 'All Time'] as $value => $label)
                        <button
                            wire:click="setDateRange('{{ $value }}')"
                            @click="open = false"
                            class="{{ $dateRange === $value ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' }} flex w-full items-center px-4 py-2 text-left text-sm transition-colors"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Aggregation Daily
            </span>
            <span
                wire:click="cycleCampaign"
                class="cursor-pointer rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                title="Click to change campaign"
            >
                Campaign {{ $selectedCampaignLabel }}
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Source Direct + UTM
            </span>
            <span
                wire:click="cycleFrequency"
                class="cursor-pointer rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                title="Click to change frequency"
            >
                Frequency {{ $frequencyFilter === 'all' ? 'All' : ucfirst(str_replace('_', ' ', $frequencyFilter)) }}
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
</div>
</x-filament-panels::page>
