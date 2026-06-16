{{-- resources/views/livewire/app/insights.blade.php --}}
<div class="space-y-8">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Insights</h1>
            <p class="mt-1 text-sm text-slate-500">Analytics and trends for your fundraising</p>
        </div>

        {{-- Period Filter --}}
        <x-ui.select wire:model.live="period" class="h-10">
            <flux:select.option value="all_time">All Time</flux:select.option>
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="yesterday">Yesterday</flux:select.option>
            <flux:select.option value="7_days">Last 7 days</flux:select.option>
            <flux:select.option value="30_days">Last 30 days</flux:select.option>
            <flux:select.option value="90_days">Last 90 days</flux:select.option>
            <flux:select.option value="this_month">This month</flux:select.option>
        </x-ui.select>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card
            label="Total Donations"
            value="{{ ($this->stats['has_approximation'] ? '≈ ' : '').'MYR '.number_format($this->stats['total_amount'] ?? 0, 2) }}"
            subtext="{{ number_format($this->stats['total_count'] ?? 0) }} donations"
        />
        <x-ui.stat-card
            label="Active Campaigns"
            value="{{ number_format($this->stats['active_campaigns'] ?? 0) }}"
        />
        <x-ui.stat-card
            label="Total Donors"
            value="{{ number_format($this->stats['total_donors'] ?? 0) }}"
        />
        <x-ui.stat-card
            label="Active Subscriptions"
            value="{{ number_format($this->stats['active_subscriptions'] ?? 0) }}"
        />
    </div>

    {{-- Charts Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Donation Trend --}}
        <x-ui.card title="Donation Trend" description="Donations over the selected period">
            @if(count($this->donationTrend) > 0)
                @php
                    $sparklineData = collect($this->donationTrend)->pluck('amount')->toArray();
                @endphp
                    <div class="flex items-end justify-between gap-4">
                    <div>
                        <div class="text-2xl font-bold text-slate-900" @if($this->donationTrendHasApproximation) title="Includes converted foreign currencies" @endif>
                            @if($this->donationTrendHasApproximation)≈ @endif MYR {{ number_format($this->donationTrendTotal, 2) }}
                        </div>
                        <div class="text-sm text-slate-500">Total in period</div>
                    </div>
                    <x-ui.sparkline
                        :data="$sparklineData"
                        :width="200"
                        :height="60"
                        color="#0d9488"
                    />
                </div>
                <div class="mt-4 flex items-end justify-between gap-1 h-32">
                    @foreach($this->donationTrend as $point)
                        @php
                            $maxAmount = max(array_column($this->donationTrend, 'amount')) ?: 1;
                            $heightPercent = $maxAmount > 0 ? ($point['amount'] / $maxAmount) * 100 : 0;
                        @endphp
                            <div class="flex flex-1 flex-col items-center gap-1" title="{{ $point['date'] }}: {{ ($point['has_approximation'] ? '≈ ' : '').'MYR '.number_format($point['amount'], 2) }}">
                            <div class="w-full rounded-t bg-teal-500/20 transition-all duration-300 hover:bg-teal-500/40" style="height: {{ max($heightPercent, 2) }}%;"></div>
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
                        @endphp
                        <div>
                                <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-slate-700">{{ $campaign['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900" @if($campaign['has_approximation']) title="Includes donations converted from foreign currencies" @endif>
                                        @if($campaign['has_approximation'])≈ @endif MYR {{ number_format($campaign['amount'], 2) }}
                                    </span>
                                    <span class="text-xs text-slate-400">{{ $percentage }}%</span>
                                </div>
                            </div>
                            <div class="w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-teal-500 transition-all duration-500" style="width: {{ $percentage }}%"></div>
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
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-slate-700">{{ $size['label'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($size['count']) }}</span>
                                    <span class="text-xs text-slate-400">{{ $size['percentage'] }}%</span>
                                </div>
                            </div>
                            <div class="w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2.5 rounded-full bg-teal-500 transition-all duration-500" style="width: {{ $size['percentage'] }}%"></div>
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
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-slate-700">{{ $method['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($method['count']) }}</span>
                                    <span class="text-xs text-slate-400">{{ $method['percentage'] }}%</span>
                                </div>
                            </div>
                            <div class="w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-teal-500 transition-all duration-500" style="width: {{ $method['percentage'] }}%"></div>
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

    {{-- Bottom Section: Recent Donations + Top Campaigns --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Recent Donations Table --}}
        <x-ui.card title="Recent Donations" description="Last 10 donations">
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
                                <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="window.location='{{ route('app.donations.show', $donation) }}'">
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
                                        {{ $donation->created_at->format('M d, Y') }}
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

        {{-- Top Campaigns by Amount --}}
        <x-ui.card title="Top Campaigns" description="By amount raised">
            @if(count($this->campaignsBreakdown) > 0)
                <div class="space-y-4">
                    @foreach($this->campaignsBreakdown as $campaign)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="size-8 rounded-lg bg-teal-50 flex items-center justify-center">
                                    <x-heroicon-o-megaphone class="size-4 text-teal-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 truncate max-w-[200px]">{{ $campaign['name'] }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-slate-900" @if($campaign['has_approximation']) title="Includes donations converted from foreign currencies" @endif>
                                @if($campaign['has_approximation'])≈ @endif MYR {{ number_format($campaign['amount'], 2) }}
                            </span>
                        </div>
                        @if(!$loop->last)
                            <div class="border-b border-slate-100"></div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-sm text-slate-400">No campaign data for this period</div>
            @endif
        </x-ui.card>
    </div>
</div>
