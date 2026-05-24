<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Recurring Plans</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Subscription status and interval breakdown</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Status Distribution</h3>
            <div class="mt-4 space-y-3">
                @foreach ($subscriptionStatusDistribution as $item)
                    <div class="flex items-center justify-between text-sm" wire:key="ssd-{{ $item['status'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                        <span class="font-medium text-gray-950 dark:text-white">{{ $item['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">By Interval</h3>
            <div class="mt-4 space-y-3">
                @forelse ($subscriptionIntervalBreakdown as $item)
                    <div class="flex items-center justify-between text-sm" wire:key="int-{{ $item['interval'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $item['interval'] }}</span>
                        <div class="text-right">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $item['count'] }}</div>
                            <div class="text-xs text-gray-400">{{ $item['total'] }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No subscription data yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
