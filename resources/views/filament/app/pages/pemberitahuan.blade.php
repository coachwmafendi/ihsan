<x-filament::page>
    @php
        $groups = [
            [
                'label' => 'Donations',
                'items' => [
                    ['label' => 'Successful donation', 'prop' => 'notifyNewDonation'],
                    ['label' => 'Refunded donation', 'prop' => 'notifyRefund'],
                    ['label' => 'Failed donation', 'prop' => 'failedPaymentNotification'],
                    ['label' => 'Large donation', 'prop' => 'notifyLargeDonation'],
                ],
            ],
            [
                'label' => 'Subscriptions',
                'items' => [
                    ['label' => 'New subscription', 'prop' => 'notifyNewSubscription'],
                    ['label' => 'Subscription cancelled', 'prop' => 'notifySubscriptionCancelled'],
                ],
            ],
            [
                'label' => 'Reports',
                'items' => [
                    ['label' => 'Daily donation summary', 'prop' => 'dailyDonationSummary'],
                    ['label' => 'Campaign milestone', 'prop' => 'notifyCampaignMilestone'],
                    ['label' => 'Monthly report', 'prop' => 'monthlyReport'],
                ],
            ],
        ];
    @endphp

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Choose which account activity emails you receive. These settings apply to all your {{ config('app.name') }} notifications.</p>

    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @foreach ($groups as $group)
            <div class="flex gap-8 py-10 first:pt-0">
                {{-- Category label --}}
                <div class="w-48 shrink-0">
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $group['label'] }}</p>
                </div>

                {{-- Checkboxes --}}
                <div class="flex flex-col gap-4">
                    @foreach ($group['items'] as $item)
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="{{ $item['prop'] }}"
                                    class="rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-800"
                                >
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                            </label>

                            {{-- Large donation threshold --}}
                            @if ($item['prop'] === 'notifyLargeDonation' && $notifyLargeDonation)
                                <div class="mt-2 ml-7 flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Threshold (RM)</span>
                                    <input
                                        type="number"
                                        wire:model.live.blur="largeDonationThreshold"
                                        min="100"
                                        step="100"
                                        class="w-28 rounded-lg border border-gray-300 bg-white px-3 py-1 text-sm text-gray-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                    >
                                    <span class="text-xs text-gray-400">≥ RM{{ number_format($largeDonationThreshold, 0) }}</span>
                                </div>
                            @endif

                            {{-- Daily summary time --}}
                            @if ($item['prop'] === 'dailyDonationSummary' && $dailyDonationSummary)
                                <div class="mt-2 ml-7 flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Send time (MYT)</span>
                                    <input
                                        type="time"
                                        wire:model.live.blur="dailySummaryTime"
                                        class="rounded-lg border border-gray-300 bg-white px-3 py-1 text-sm text-gray-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                    >
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400 mt-2">Changes are saved automatically.</p>
</x-filament::page>
