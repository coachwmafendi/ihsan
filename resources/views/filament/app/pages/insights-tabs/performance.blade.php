<div>
    <x-ui.section-header
        title="Performance"
        subtitle="Monthly revenue and campaign performance"
    />

    <div class="mt-8">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Monthly Revenue (12 months)</h3>
        <div class="mt-4 flex h-48 items-end gap-2 border-b border-gray-200 pb-2 dark:border-gray-700">
            @php
                $maxAmount = max(1, ...array_map(fn($m) => (float) $m['amount'], $monthlyRevenue));
            @endphp
            @foreach ($monthlyRevenue as $month)
                <div class="flex h-full min-w-0 flex-1 flex-col justify-end gap-1" wire:key="mrev-{{ $month['month'] }}">
                    <div
                        class="rounded-t-md bg-primary-500/80"
                        style="height: {{ max(8, round(((float) $month['amount'] / $maxAmount) * 100)) }}%"
                        title="{{ $month['month'] }}: MYR {{ $month['amount'] }}"
                    ></div>
                    <div class="truncate text-center text-xs text-gray-500 dark:text-gray-400">{{ $month['month'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        @if (count($campaignPerformance) > 0)
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Campaign Comparison</h3>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($campaignPerformance as $camp)
                        <x-ui.data-row :label="$camp['campaign']" wire:key="camp-{{ $camp['campaign'] }}">
                            {{ $camp['total'] }}
                        </x-ui.data-row>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Trends</h3>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($monthlyRevenue as $month)
                    <div class="flex items-center justify-between py-2 text-sm" wire:key="trend-{{ $month['month'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $month['month'] }}</span>
                        <div class="text-right">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $month['successRate'] }} success</div>
                            <div class="text-xs text-gray-400">Avg MYR {{ $month['averageAmount'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
