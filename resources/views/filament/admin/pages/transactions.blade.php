<x-filament-panels::page>
    <div class="ihsan-admin-page">
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Amount</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">
                    MYR {{ number_format($this->totals['amount'], 2) }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Fee</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">
                    MYR {{ number_format($this->totals['fee'], 2) }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Org Receives</p>
                <p class="text-xl font-semibold text-gray-900 dark:text-white">
                    MYR {{ number_format($this->totals['org_receives'], 2) }}
                </p>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
