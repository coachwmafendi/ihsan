<x-filament::page>
    @php
        $toggleCards = [
            ['label' => 'Derma Baharu', 'desc' => 'Terima e-mel untuk setiap derma berjaya', 'prop' => 'notifyNewDonation', 'active' => $notifyNewDonation],
            ['label' => 'Ringkasan Harian', 'desc' => 'Terima ringkasan semua derma yang diterima setiap hari', 'prop' => 'dailyDonationSummary', 'active' => $dailyDonationSummary],
            ['label' => 'Bayaran Gagal', 'desc' => 'Amaran apabila bayaran berulang penderma gagal diproses', 'prop' => 'failedPaymentNotification', 'active' => $failedPaymentNotification],
            ['label' => 'Langganan Baharu', 'desc' => 'Terima e-mel apabila ada langganan bulanan baharu', 'prop' => 'notifyNewSubscription', 'active' => $notifyNewSubscription],
            ['label' => 'Langganan Dibatalkan', 'desc' => 'Terima e-mel apabila penderma membatalkan langganan', 'prop' => 'notifySubscriptionCancelled', 'active' => $notifySubscriptionCancelled],
            ['label' => 'Derma Besar', 'desc' => 'Terima e-mel khas untuk derma melebihi ambang tertentu', 'prop' => 'notifyLargeDonation', 'active' => $notifyLargeDonation],
            ['label' => 'Refund', 'desc' => 'Terima e-mel apabila ada derma direfund', 'prop' => 'notifyRefund', 'active' => $notifyRefund],
            ['label' => 'Sasaran Kempen', 'desc' => 'Notifikasi apabila kempen menghampiri sasaran', 'prop' => 'notifyCampaignMilestone', 'active' => $notifyCampaignMilestone],
            ['label' => 'Laporan Bulanan', 'desc' => 'Terima laporan ringkasan derma setiap bulan', 'prop' => 'monthlyReport', 'active' => $monthlyReport],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($toggleCards as $card)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xs flex flex-col">
                <div class="flex items-start justify-between gap-3 px-5 pt-5 pb-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $card['label'] }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $card['desc'] }}</p>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        aria-checked="{{ $card['active'] ? 'true' : 'false' }}"
                        wire:click="$toggle('{{ $card['prop'] }}')"
                        class="relative shrink-0 mt-0.5 inline-flex h-6 w-10 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 {{ $card['active'] ? 'bg-teal-600' : 'bg-gray-200 dark:bg-gray-600' }}"
                    >
                        <span class="pointer-events-none inline-block size-4 rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $card['active'] ? 'translate-x-[18px]' : 'translate-x-0.5' }}"></span>
                    </button>
                </div>
                @if ($card['prop'] === 'dailyDonationSummary')
                    <div class="mt-auto border-t border-gray-100 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800/50 rounded-b-xl">
                        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Masa Hantar</label>
                        <input
                            type="time"
                            wire:model.live.blur="dailySummaryTime"
                            {{ $dailyDonationSummary ? '' : 'disabled' }}
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                        <p class="mt-1 text-xs text-gray-400">Waktu Malaysia (MYT)</p>
                    </div>
                @endif
                @if ($card['prop'] === 'notifyLargeDonation')
                    <div class="mt-auto border-t border-gray-100 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-800/50 rounded-b-xl">
                        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Ambang (RM)</label>
                        <input
                            type="number"
                            wire:model.live.blur="largeDonationThreshold"
                            min="100"
                            step="100"
                            {{ $notifyLargeDonation ? '' : 'disabled' }}
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                        <p class="mt-1 text-xs text-gray-400">≥ RM{{ number_format($largeDonationThreshold, 0) }}</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        <p class="text-xs text-gray-400">Perubahan disimpan secara automatik.</p>
    </div>
</x-filament::page>
