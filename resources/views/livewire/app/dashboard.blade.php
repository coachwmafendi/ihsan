{{-- resources/views/livewire/app/dashboard.blade.php --}}
<div class="space-y-8">
    {{-- Page Header --}}
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Overview of your fundraising activity</p>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($this->stats as $stat)
            <x-ui.card>
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</div>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-slate-900">{{ $stat['value'] }}</span>
                            @if($stat['trend'])
                                <span class="text-xs font-medium {{ $stat['trendUp'] ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $stat['trend'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @if(!empty($stat['sparkline']))
                        <x-ui.sparkline
                            :data="$stat['sparkline']"
                            :width="100"
                            :height="40"
                            class="opacity-60"
                        />
                    @endif
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        {{-- Left Column: Charts & Tables --}}
        <div class="lg:col-span-8 space-y-8">
            {{-- Quick Actions --}}
            <x-ui.card title="Quick actions">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <x-ui.button href="/app/campaigns/create" variant="primary">
                        <x-heroicon-o-plus class="size-4" />
                        Create Campaign
                    </x-ui.button>

                    <x-ui.button href="/app/donations" variant="outline">
                        <x-heroicon-o-banknotes class="size-4" />
                        View Donations
                    </x-ui.button>

                    <x-ui.button href="/app/virtual-terminal" variant="outline">
                        <x-heroicon-o-device-phone-mobile class="size-4" />
                        Virtual Terminal
                    </x-ui.button>
                </div>
            </x-ui.card>

            {{-- Top Campaigns --}}
            <x-ui.card title="Top campaigns" description="Your best performing campaigns">
                @if($this->topCampaigns->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($this->topCampaigns as $campaign)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="size-8 rounded-lg bg-teal-50 flex items-center justify-center">
                                        <x-heroicon-o-megaphone class="size-4 text-teal-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ $campaign->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $campaign->donations_count ?? 0 }} donations</p>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-slate-900">
                                    RM {{ number_format(($campaign->donations_sum_gross_amount ?? 0), 2) }}
                                </span>
                            </div>
                            @if(!$loop->last)
                                <div class="border-b border-slate-100"></div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state
                        icon="heroicon-o-megaphone"
                        title="No campaigns yet"
                        description="Create your first campaign to start fundraising."
                        action-label="Create Campaign"
                        action-url="/app/campaigns/create"
                    />
                @endif
            </x-ui.card>
        </div>

        {{-- Right Column: Recent Activity --}}
        <div class="lg:col-span-4 space-y-8">
            <x-ui.card title="Recent activity">
                @if($this->recentDonations->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($this->recentDonations as $donation)
                            <a href="/app/donations/{{ $donation->public_id }}" wire:navigate class="block group">
                                <div class="flex items-start gap-3">
                                    <div class="size-8 rounded-full bg-slate-50 flex items-center justify-center flex-shrink-0">
                                        <x-heroicon-o-user class="size-4 text-slate-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            {{ $donation->donor?->full_name ?? 'Anonymous' }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            RM {{ number_format($donation->gross_amount, 2) }}
                                            <span class="text-slate-300">·</span>
                                            {{ $donation->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <x-ui.badge status="{{ $donation->status }}" size="sm">
                                        {{ ucfirst($donation->status) }}
                                    </x-ui.badge>
                                </div>
                            </a>
                            @if(!$loop->last)
                                <div class="border-b border-slate-100"></div>
                            @endif
                        @endforeach
                    </div>

                    <x-slot:footer>
                        <a href="/app/donations" wire:navigate class="text-sm font-medium text-teal-600 hover:text-teal-700 transition-colors">
                            View all donations →
                        </a>
                    </x-slot:footer>
                @else
                    <x-ui.empty-state
                        icon="heroicon-o-banknotes"
                        title="No recent donations"
                        description="When donations arrive, they'll appear here."
                    />
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
