<div>
    <x-ui.section-header
        title="Recurring Revenue"
        subtitle="MRR and subscription activity trends"
    />

    <div class="mt-8 grid gap-4 md:grid-cols-3">
        <x-ui.stat-card
            label="Current MRR"
            value="{{ $mrrOverview['currentMrr'] }}"
            subtext="{{ $mrrOverview['activeCount'] }} active subscriptions"
        />
        <x-ui.stat-card
            label="New this month"
            value="{{ $mrrOverview['newThisMonth'] }}"
            trend="New subscriptions"
            trendColor="success"
        />
        <x-ui.stat-card
            label="Cancelled this month"
            value="{{ $mrrOverview['cancelledThisMonth'] }}"
            trend="Cancelled"
            trendColor="danger"
        />
    </div>

    @if (count($subscriptionTrend) > 0)
        <div class="mt-8">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Subscription Trend (6 months)</h3>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($subscriptionTrend as $trend)
                    <x-ui.data-row :label="$trend['month']" wire:key="trend-{{ $trend['month'] }}">
                        <div class="flex gap-6 text-right">
                            <div>
                                <div class="text-xs text-gray-400">New</div>
                                <div class="font-medium text-green-600 dark:text-green-400">{{ $trend['newSubs'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">Cancelled</div>
                                <div class="font-medium text-red-600 dark:text-red-400">{{ $trend['cancelledSubs'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">Active</div>
                                <div class="font-medium text-gray-950 dark:text-white">{{ $trend['totalActive'] }}</div>
                            </div>
                        </div>
                    </x-ui.data-row>
                @endforeach
            </div>
        </div>
    @endif
</div>
