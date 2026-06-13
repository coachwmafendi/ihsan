<div class="space-y-6">
    <x-ui.page-header title="Settings">
        <x-slot:subtitle>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-slate-500">
                    <li>Settings</li>
                    <li>
                        <svg class="mx-1 h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li class="font-medium text-slate-900">Notifications</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>

    <x-ui.card title="Email Notifications" description="Choose which account activity emails you receive. Changes are saved automatically.">
        <div class="divide-y divide-slate-100">
            {{-- Donations --}}
            <div class="flex flex-col gap-6 py-6 first:pt-0 md:flex-row md:gap-8">
                <div class="w-48 shrink-0">
                    <p class="text-sm font-semibold text-slate-900">Donations</p>
                </div>
                <div class="flex flex-col gap-4">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="notifyNewDonation" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Successful donation</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="notifyRefund" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Refunded donation</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="failedPaymentNotification" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Failed donation</span>
                    </label>

                    <div>
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model.live="notifyLargeDonation" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-sm text-slate-700">Large donation</span>
                        </label>

                        @if ($notifyLargeDonation)
                            <div class="mt-2 ml-7 flex items-center gap-2">
                                <span class="text-xs text-slate-500">Threshold (RM)</span>
                                <input
                                    type="number"
                                    wire:model.live.blur="largeDonationThreshold"
                                    min="100"
                                    step="100"
                                    class="w-28 rounded-lg border border-slate-300 bg-white px-3 py-1 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                <span class="text-xs text-slate-400">≥ RM{{ number_format($largeDonationThreshold, 0) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Subscriptions --}}
            <div class="flex flex-col gap-6 py-6 md:flex-row md:gap-8">
                <div class="w-48 shrink-0">
                    <p class="text-sm font-semibold text-slate-900">Subscriptions</p>
                </div>
                <div class="flex flex-col gap-4">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="notifyNewSubscription" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">New subscription</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="notifySubscriptionCancelled" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Subscription cancelled</span>
                    </label>
                </div>
            </div>

            {{-- Reports --}}
            <div class="flex flex-col gap-6 py-6 md:flex-row md:gap-8">
                <div class="w-48 shrink-0">
                    <p class="text-sm font-semibold text-slate-900">Reports</p>
                </div>
                <div class="flex flex-col gap-4">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="dailyDonationSummary" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Daily donation summary (12:00 AM)</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="weeklyReport" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Weekly report (Mon 08:00 AM)</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="notifyCampaignMilestone" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Campaign milestone</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" wire:model.live="monthlyReport" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-slate-700">Monthly report (1st day 08:00 AM, previous month)</span>
                    </label>
                </div>
            </div>
        </div>
    </x-ui.card>
</div>
