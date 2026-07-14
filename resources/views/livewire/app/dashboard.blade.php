{{-- resources/views/livewire/app/dashboard.blade.php --}}
<div
    x-data="{ loaded: false }"
    x-init="$nextTick(() => setTimeout(() => loaded = true, 350))"
    class="relative"
>
    {{-- Initial loading skeleton --}}
    <div
        x-show="! loaded"
        x-transition.opacity.duration.250ms
        x-cloak
        aria-hidden="true"
        class="absolute inset-0 z-10 bg-[#f7f7fb]"
    >
        <div class="space-y-8">
            {{-- Header skeleton --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="h-8 w-48 animate-pulse rounded bg-slate-200"></div>
                    <div class="mt-1 h-4 w-36 animate-pulse rounded bg-slate-200"></div>
                </div>
                <div class="h-9 w-80 animate-pulse rounded-lg bg-slate-200"></div>
            </div>

            {{-- Quick actions skeleton --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                    <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                    <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                </div>
            </div>

            {{-- Stats grid skeleton --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @for ($i = 0; $i < 8; $i++)
                    <div class="h-28 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="h-3 w-24 animate-pulse rounded bg-slate-200"></div>
                        <div class="mt-3 h-7 w-32 animate-pulse rounded bg-slate-200"></div>
                    </div>
                @endfor
            </div>

            {{-- Charts grid skeleton --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @for ($i = 0; $i < 4; $i++)
                    <div class="h-80 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-1 h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                        <div class="h-4 w-56 animate-pulse rounded bg-slate-200"></div>
                        <div class="mt-8 space-y-4">
                            <div class="h-3 w-full animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-5/6 animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-4/5 animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-full animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-2/3 animate-pulse rounded bg-slate-200"></div>
                        </div>
                    </div>
                @endfor
            </div>

            {{-- Recent donations skeleton --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                <div class="space-y-3">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="size-8 animate-pulse rounded-full bg-slate-200"></div>
                                <div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div>
                            </div>
                            <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Loading overlay for period/filter changes --}}
    <div wire:loading.delay class="absolute inset-0 z-20 bg-white/80 backdrop-blur-sm" aria-hidden="true">
        <div class="flex h-full items-center justify-center">
            <x-heroicon-o-arrow-path class="size-8 animate-spin text-slate-400" />
        </div>
    </div>

    <div class="space-y-8">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Overview of your fundraising activity</p>
        </div>

        <div class="flex flex-col items-start gap-3 sm:items-end">
            <div class="flex max-w-full overflow-x-auto rounded-xl border border-slate-200 bg-slate-100 p-1 shadow-sm [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button
                    type="button"
                    wire:click="$set('period', 'today')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'today' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    Today
                </button>
                <button
                    type="button"
                    wire:click="$set('period', 'yesterday')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'yesterday' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    Yesterday
                </button>
                <button
                    type="button"
                    wire:click="$set('period', '7_days')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === '7_days' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    7D
                </button>
                <button
                    type="button"
                    wire:click="$set('period', '30_days')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === '30_days' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    30D
                </button>
                <button
                    type="button"
                    wire:click="$set('period', '90_days')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === '90_days' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    90D
                </button>
                <button
                    type="button"
                    wire:click="$set('period', 'custom')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'custom' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    Custom
                </button>
            </div>

            @if($period === 'custom')
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        type="date"
                        wire:model.live="customFrom"
                        class="block rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                    <span class="text-sm text-slate-400">to</span>
                    <input
                        type="date"
                        wire:model.live="customTo"
                        class="block rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <x-ui.card title="Quick actions">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-ui.button wire:click="openCreateCampaignModal" variant="primary">
                <x-heroicon-o-plus class="size-4" />
                Create Campaign
            </x-ui.button>

            <x-ui.button href="/app/donations" variant="outline">
                <x-heroicon-o-banknotes class="size-4" />
                View Donations
            </x-ui.button>

            <x-ui.button href="/app/virtual-terminal" variant="outline" target="_blank">
                <x-heroicon-o-device-phone-mobile class="size-4" />
                Virtual Terminal
                <x-heroicon-o-arrow-top-right-on-square aria-label="Opens in new tab" class="size-4" />
            </x-ui.button>
        </div>
    </x-ui.card>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card
            label="Total Donations"
            value="{{ ($this->stats['has_approximation'] ? '≈ ' : '').'MYR '.number_format($this->stats['total_amount'] ?? 0, 2) }}"
            subtext="{{ number_format($this->stats['total_count'] ?? 0) }} donations"
        />
        <x-ui.stat-card
            label="Donors"
            value="{{ number_format($this->stats['total_donors'] ?? 0) }}"
        />
        <x-ui.stat-card
            label="Active Campaigns"
            value="{{ number_format($this->stats['active_campaigns'] ?? 0) }}"
        />
        <x-ui.stat-card
            label="Active Subscriptions"
            value="{{ number_format($this->stats['active_subscriptions'] ?? 0) }}"
        />
        <x-ui.stat-card
            label="Avg Donation"
            value="MYR {{ number_format(($this->stats['total_count'] ?? 0) > 0 ? ($this->stats['total_amount'] ?? 0) / ($this->stats['total_count'] ?? 1) : 0, 2) }}"
        />
        <x-ui.stat-card
            label="MRR"
            value="{{ ($this->recurringHealth['mrr_has_approximation'] ? '≈ ' : '').'MYR '.number_format($this->recurringHealth['mrr'] ?? 0, 2) }}"
            subtext="Monthly recurring revenue"
        />
        <x-ui.stat-card
            label="At-risk Subscriptions"
            value="{{ number_format($this->recurringHealth['at_risk_count'] ?? 0) }}"
            subtext="Past due or failed"
        />
        <x-ui.stat-card
            label="Expected (30 days)"
            value="{{ ($this->recurringHealth['expected_30_days_has_approximation'] ? '≈ ' : '').'MYR '.number_format($this->recurringHealth['expected_30_days'] ?? 0, 2) }}"
            subtext="Scheduled recurring charges"
        />
    </div>

    {{-- Charts Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Donation Trend --}}
        <x-ui.card title="Donation Trend" description="Donations over the selected period">
            @if(count($this->donationTrend) > 0)
                @php
                    $sparklineData = collect($this->donationTrend)->pluck('amount')->toArray();
                    $donationTrendTotal = array_sum($sparklineData);
                    $donationTrendHasApproximation = collect($this->donationTrend)->contains('has_approximation', true);
                @endphp

                <div class="flex items-end justify-between gap-4">
                    <div>
                        <div class="text-2xl font-bold text-slate-900">
                            @if($donationTrendHasApproximation)
                                <x-ui.tooltip text="Includes converted foreign currencies">
                                    <span>≈ MYR {{ number_format($donationTrendTotal, 2) }}</span>
                                </x-ui.tooltip>
                            @else
                                <span>MYR {{ number_format($donationTrendTotal, 2) }}</span>
                            @endif
                        </div>
                        <div class="text-sm text-slate-500">Total in period</div>
                    </div>
                    <x-ui.sparkline
                        :data="$sparklineData"
                        :width="200"
                        :height="60"
                        color="#3b82f6"
                    />
                </div>

                <div class="mt-4 flex h-32 items-end justify-between gap-1">
                    @foreach($this->donationTrend as $point)
                        @php
                            $maxAmount = max(array_column($this->donationTrend, 'amount')) ?: 1;
                            $heightPercent = $maxAmount > 0 ? ($point['amount'] / $maxAmount) * 100 : 0;
                        @endphp
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <x-ui.tooltip :text="$point['date'].': '.($point['has_approximation'] ? '≈ ' : '').'MYR '.number_format($point['amount'], 2)">
                                <div class="w-full rounded-t bg-blue-500/20 transition-all duration-300 hover:bg-blue-500/40" style="height: {{ max($heightPercent, 2) }}%;"></div>
                            </x-ui.tooltip>
                            @if(count($this->donationTrend) <= 14 || $loop->index % ceil(count($this->donationTrend) / 7) === 0)
                                <span class="text-[10px] text-slate-400">{{ $point['date'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-sm text-slate-400">No donation data for this period</div>
            @endif
        </x-ui.card>

        {{-- Donations by Campaign --}}
        <x-ui.card title="Donations by Campaign" description="Top 5 campaigns by amount raised">
            @if(count($this->campaignsBreakdown) > 0)
                @php
                    $totalCampaignAmount = array_sum(array_column($this->campaignsBreakdown, 'amount')) ?: 1;
                @endphp
                <div class="space-y-4">
                    @foreach($this->campaignsBreakdown as $campaign)
                        @php
                            $percentage = $totalCampaignAmount > 0 ? round(($campaign['amount'] / $totalCampaignAmount) * 100) : 0;
                            $campaignApprox = $campaign['has_approximation'] ? '≈ ' : '';
                        @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">{{ $campaign['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    @if ($campaign['has_approximation'])
                                        <x-ui.tooltip text="Includes donations converted from foreign currencies">
                                            <span class="text-sm font-semibold text-slate-900">{{ $campaignApprox }}MYR {{ number_format($campaign['amount'], 2) }}</span>
                                        </x-ui.tooltip>
                                    @else
                                        <span class="text-sm font-semibold text-slate-900">{{ $campaignApprox }}MYR {{ number_format($campaign['amount'], 2) }}</span>
                                    @endif
                                    <span class="text-xs text-slate-400">{{ $percentage }}%</span>
                                </div>
                            </div>
                            <div class="w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-blue-500 transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-sm text-slate-400">No campaign data for this period</div>
            @endif
        </x-ui.card>

        {{-- Donation Sizes --}}
        <x-ui.card title="Donation Sizes" description="Distribution by amount range">
            @if(count($this->donationSizes) > 0)
                @php
                    $totalSizeCount = array_sum(array_column($this->donationSizes, 'count')) ?: 1;
                @endphp
                <div class="space-y-4">
                    @foreach($this->donationSizes as $size)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">{{ $size['label'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($size['count']) }}</span>
                                    <span class="text-xs text-slate-400">{{ $size['percentage'] }}%</span>
                                </div>
                            </div>
                            <div class="w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2.5 rounded-full bg-blue-500 transition-all duration-500" style="width: {{ $size['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 text-center text-xs text-slate-400">{{ number_format($totalSizeCount) }} total donations</div>
            @else
                <div class="py-8 text-center text-sm text-slate-400">No donation data for this period</div>
            @endif
        </x-ui.card>

        {{-- Payment Methods --}}
        <x-ui.card title="Payment Methods" description="Breakdown by payment type">
            @if(count($this->paymentMethods) > 0)
                @php
                    $totalMethodCount = array_sum(array_column($this->paymentMethods, 'count')) ?: 1;
                @endphp
                <div class="space-y-4">
                    @foreach($this->paymentMethods as $method)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">{{ $method['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($method['count']) }}</span>
                                    <span class="text-xs text-slate-400">{{ $method['percentage'] }}%</span>
                                </div>
                            </div>
                            <div class="w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-blue-500 transition-all duration-500" style="width: {{ $method['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 text-center text-xs text-slate-400">{{ number_format($totalMethodCount) }} total payments</div>
            @else
                <div class="py-8 text-center text-sm text-slate-400">No payment data for this period</div>
            @endif
        </x-ui.card>
    </div>

    {{-- Recent Donations --}}
    <x-ui.card title="Recent Donations" description="Last 10 donations in the selected period">
        @if($this->recentDonations->isNotEmpty())
            <div class="overflow-x-auto -mx-5 -my-5">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Donor</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($this->recentDonations as $donation)
                            <tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="window.location='{{ route('app.donations.show', $donation) }}'">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="size-6 rounded-full bg-slate-100 flex items-center justify-center">
                                            <x-heroicon-o-user class="size-3 text-slate-400" />
                                        </div>
                                        <span class="text-sm text-slate-900">{{ $donation->donor?->name ?? 'Anonymous' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <x-donation-report-amount :donation="$donation" />
                                </td>
                                <td class="px-5 py-3 text-sm text-slate-600">
                                    {{ $donation->campaign?->title ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-sm text-slate-500">
                                    {{ myrTime($donation->created_at, withLabel: false, format: 'M d, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-8 text-center text-sm text-slate-400">No donations in this period</div>
        @endif
    </x-ui.card>

    <livewire:app.campaigns.campaign-create-modal />
</div>
</div>
