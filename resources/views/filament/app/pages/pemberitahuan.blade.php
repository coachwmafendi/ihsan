<x-filament::page>
    @php
        $toggleRows = [
            ['label' => 'Notifikasi derma baharu', 'desc' => 'Terima e-mel untuk setiap derma berjaya', 'prop' => 'notifyNewDonation', 'active' => $notifyNewDonation],
            ['label' => 'Ringkasan derma harian', 'desc' => 'Terima ringkasan semua derma yang diterima setiap hari', 'prop' => 'dailyDonationSummary', 'active' => $dailyDonationSummary],
            ['label' => 'Notifikasi bayaran bulanan gagal', 'desc' => 'Amaran apabila bayaran berulang penderma gagal diproses', 'prop' => 'failedPaymentNotification', 'active' => $failedPaymentNotification],
            ['label' => 'Notifikasi langganan baharu', 'desc' => 'Terima e-mel apabila ada langganan bulanan baharu', 'prop' => 'notifyNewSubscription', 'active' => $notifyNewSubscription],
            ['label' => 'Langganan dibatalkan', 'desc' => 'Terima e-mel apabila penderma membatalkan langganan', 'prop' => 'notifySubscriptionCancelled', 'active' => $notifySubscriptionCancelled],
            ['label' => 'Notifikasi derma besar', 'desc' => 'Terima e-mel khas untuk derma melebihi ambang tertentu', 'prop' => 'notifyLargeDonation', 'active' => $notifyLargeDonation],
            ['label' => 'Notifikasi refund', 'desc' => 'Terima e-mel apabila ada derma direfund', 'prop' => 'notifyRefund', 'active' => $notifyRefund],
            ['label' => 'Sasaran kempen hampir tercapai', 'desc' => 'Terima notifikasi apabila kempen menghampiri sasaran', 'prop' => 'notifyCampaignMilestone', 'active' => $notifyCampaignMilestone],
            ['label' => 'Laporan bulanan', 'desc' => 'Terima laporan ringkasan derma setiap bulan', 'prop' => 'monthlyReport', 'active' => $monthlyReport],
        ];
    @endphp

    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 max-w-lg">
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($toggleRows as $row)
                <div>
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $row['desc'] }}</p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            aria-checked="{{ $row['active'] ? 'true' : 'false' }}"
                            wire:click="$toggle('{{ $row['prop'] }}')"
                            class="relative shrink-0 inline-flex h-6 w-10 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 {{ $row['active'] ? 'bg-teal-600' : 'bg-gray-200 dark:bg-gray-600' }}"
                        >
                            <span class="pointer-events-none inline-block size-4 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $row['active'] ? 'translate-x-[18px]' : 'translate-x-0.5' }}"></span>
                        </button>
                    </div>
                    @if ($row['prop'] === 'dailyDonationSummary' && $dailyDonationSummary)
                        <div class="border-t border-gray-100 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800/50">
                            <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Masa hantar ringkasan</label>
                            <input
                                type="time"
                                wire:model.blur="dailySummaryTime"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            >
                            <p class="mt-1 text-xs text-gray-400">Waktu Malaysia (MYT)</p>
                        </div>
                    @endif
                    @if ($row['prop'] === 'notifyLargeDonation' && $notifyLargeDonation)
                        <div class="border-t border-gray-100 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800/50">
                            <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Ambang derma besar (RM)</label>
                            <input
                                type="number"
                                wire:model.blur="largeDonationThreshold"
                                min="100"
                                step="100"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            >
                            <p class="mt-1 text-xs text-gray-400">Notifikasi akan dihantar untuk derma ≥ RM{{ number_format($largeDonationThreshold, 0) }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="border-t border-gray-200 bg-gray-50 px-5 py-2.5 dark:border-gray-700 dark:bg-gray-800/50">
            <p class="text-xs text-gray-400">Perubahan disimpan secara automatik.</p>
        </div>
    </div>
</x-filament::page>
