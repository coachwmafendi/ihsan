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
            <x-ui.skeleton height="2.25rem" width="8rem" rounded="0.5rem" />
            <x-ui.skeleton height="2.25rem" width="7rem" rounded="0.5rem" />
            <x-ui.skeleton height="2.25rem" width="9rem" rounded="0.5rem" />
            <x-ui.skeleton height="2.25rem" width="8rem" rounded="0.5rem" />
            <x-ui.skeleton height="2.25rem" width="7rem" rounded="0.5rem" />
        </div>

        {{-- KPI cards skeleton --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @for ($i = 0; $i < 4; $i++)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-3">
                    <x-ui.skeleton height="1rem" width="6rem" />
                    <x-ui.skeleton height="2rem" width="9rem" />
                    <x-ui.skeleton height="1rem" width="10rem" />
                </div>
            @endfor
        </div>

        {{-- Main + sidebar skeleton --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <x-ui.skeleton height="1.25rem" width="6rem" />
                        <x-ui.skeleton height="1rem" width="10rem" />
                    </div>
                    <x-ui.skeleton height="2.25rem" width="7rem" rounded="0.5rem" />
                </div>
                <div class="mt-4 flex h-64 items-end gap-3 border-b border-gray-200 pb-2 dark:border-gray-700">
                    @for ($i = 0; $i < 7; $i++)
                        <div class="flex h-full min-w-0 flex-1 flex-col justify-end gap-2">
                            <x-ui.skeleton height="{{ rand(20, 90) }}%" width="100%" rounded="0.375rem 0.375rem 0 0" />
                            <x-ui.skeleton height="0.75rem" width="100%" />
                        </div>
                    @endfor
                </div>
                <div class="grid gap-6 xl:grid-cols-2">
                    @for ($col = 0; $col < 2; $col++)
                        <div class="space-y-3">
                            <x-ui.skeleton height="1.25rem" width="8rem" />
                            @for ($i = 0; $i < 5; $i++)
                                <div class="flex items-center justify-between py-2">
                                    <x-ui.skeleton height="1rem" width="6rem" />
                                    <x-ui.skeleton height="1rem" width="4rem" />
                                </div>
                            @endfor
                        </div>
                    @endfor
                </div>
            </div>
            <div class="space-y-3">
                @for ($i = 0; $i < 9; $i++)
                    <x-ui.skeleton height="1.75rem" width="100%" rounded="0.5rem" />
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
        {{-- Filter Bar --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Date range dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <x-ui.filter-pill @click="open = !open" @click.away="open = false">
                    Date @switch($dateRange)
                        @case('7_days') Last 7 days @break
                        @case('30_days') Last 30 days @break
                        @case('90_days') Last 90 days @break
                        @case('this_month') This month @break
                        @default All Time
                    @endswitch
                </x-ui.filter-pill>
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

            <x-ui.filter-pill :static="true">Aggregation Daily</x-ui.filter-pill>

            <x-ui.filter-pill wire:click="cycleCampaign" title="Click to change campaign">
                Campaign {{ $selectedCampaignLabel }}
            </x-ui.filter-pill>

            <x-ui.filter-pill :static="true">Source Direct + UTM</x-ui.filter-pill>

            <x-ui.filter-pill wire:click="cycleFrequency" title="Click to change frequency">
                Frequency {{ $frequencyFilter === 'all' ? 'All' : ucfirst(str_replace('_', ' ', $frequencyFilter)) }}
            </x-ui.filter-pill>
        </div>

        {{-- KPI Cards --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card
                label="Total raised"
                value="MYR {{ $totalRaised }}"
                subtext="{{ $successfulDonationsCount }} successful donations"
            />
            <x-ui.stat-card
                label="Recurring revenue"
                value="MYR {{ $monthlyRecurringRevenue }}"
                subtext="{{ $activeRecurringDonors }} active recurring donors"
            />
            <x-ui.stat-card
                label="One-time donations"
                value="MYR {{ $oneTimeDonationsTotal }}"
                subtext="Average MYR {{ $averageDonationAmount }}"
            />
            <x-ui.stat-card
                label="First installments"
                value="MYR {{ $firstInstallmentsTotal }}"
                subtext="{{ $successRate }}% payment success rate"
            />
        </div>

        {{-- Main content + tab sidebar --}}
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
                        <x-ui.section-header
                            title="Overview"
                            subtitle="Performance over the last 7 days"
                        >
                            <x-slot:action>
                                <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                                    Metric Revenue
                                </div>
                            </x-slot:action>
                        </x-ui.section-header>

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
                                        <x-ui.data-row :label="$row['label']" wire:key="status-{{ $row['label'] }}">
                                            {{ $row['value'] }}
                                        </x-ui.data-row>
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

            {{-- Tab Sidebar --}}
            <div class="space-y-1">
                @php
                    $tabs = [
                        'overview'          => 'Overview',
                        'performance'       => 'Performance',
                        'recurring-plans'   => 'Recurring plans',
                        'recurring-revenue' => 'Recurring revenue',
                        'retention'         => 'Retention',
                        'payment-methods'   => 'Payment methods',
                        'frequencies'       => 'Frequencies',
                        'elements'          => 'Elements',
                        'url'               => 'URL',
                    ];
                @endphp

                @foreach ($tabs as $key => $label)
                    <x-ui.tab-item
                        :active="$activeTab === $key"
                        wire:click="setActiveTab('{{ $key }}')"
                    >
                        {{ $label }}
                    </x-ui.tab-item>
                @endforeach
            </div>
        </div>
    </div>
</div>
</x-filament-panels::page>
