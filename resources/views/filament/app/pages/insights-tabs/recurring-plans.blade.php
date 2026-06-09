<div>
    <x-ui.section-header
        title="Recurring Plans"
        subtitle="Subscription status and interval breakdown"
    />

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Status Distribution</h3>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($subscriptionStatusDistribution as $item)
                    <x-ui.data-row :label="$item['label']" wire:key="ssd-{{ $item['status'] }}">
                        {{ $item['count'] }}
                    </x-ui.data-row>
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">By Interval</h3>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($subscriptionIntervalBreakdown as $item)
                    <div class="flex items-center justify-between py-2 text-sm" wire:key="int-{{ $item['interval'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $item['interval'] }}</span>
                        <div class="text-right">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $item['count'] }}</div>
                            <div class="text-xs text-gray-400">{{ $item['total'] }}</div>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-sm text-gray-400">No subscription data yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
