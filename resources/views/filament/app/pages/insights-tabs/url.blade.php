<div>
    <x-ui.section-header
        title="URL"
        subtitle="Campaign donation page performance"
    />

    @if (count($campaignUrlPerformance) > 0)
        <div class="mt-8 divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($campaignUrlPerformance as $camp)
                <div class="flex items-center justify-between py-3 text-sm" wire:key="url-{{ $camp['campaign'] }}">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $camp['campaign'] }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $camp['totalDonations'] }} total donations</div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $camp['totalAmount'] }}</div>
                        <div class="text-xs text-gray-400">{{ $camp['donationCount'] }} succeeded</div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="mt-8 flex items-center justify-center rounded-lg border border-dashed border-gray-300 p-12 dark:border-gray-700">
            <p class="text-sm text-gray-400">No donation data yet for campaigns.</p>
        </div>
    @endif
</div>
