<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Recurring Revenue</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">MRR and subscription activity trends</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Current MRR</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $mrrOverview['currentMrr'] }}</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $mrrOverview['activeCount'] }} active subscriptions</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-green-600 dark:text-green-400">New this month</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $mrrOverview['newThisMonth'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-red-600 dark:text-red-400">Cancelled this month</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $mrrOverview['cancelledThisMonth'] }}</div>
        </x-filament::section>
    </div>

    @if (count($subscriptionTrend) > 0)
        <div class="mt-8">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Subscription Trend (6 months)</h3>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($subscriptionTrend as $trend)
                    <div class="flex items-center justify-between py-3 text-sm" wire:key="trend-{{ $trend['month'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $trend['month'] }}</span>
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
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
