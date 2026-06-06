<x-filament::page>
    <div class="space-y-6">
        {{-- Month Selector --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Billing Overview</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">View your monthly donation volume and fees.</p>
            </div>
            <div class="w-48">
                <select
                    wire:model.live="selectedMonth"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                >
                    @foreach ($availableMonths as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount Processed</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">RM {{ $amountProcessed }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ $totalTransactions }} transactions</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stripe Fee</p>
                <p class="mt-2 text-2xl font-bold text-red-600">- RM {{ $stripeFee }}</p>
                <p class="mt-1 text-xs text-gray-400">Payment provider fee</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ config('app.name') }} Fee ({{ $processingFeeRate }}%)</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">- RM {{ $processingFee }}</p>
                <p class="mt-1 text-xs text-gray-400">Auto-deducted</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Received</p>
                <p class="mt-2 text-2xl font-bold text-emerald-600">RM {{ $netReceived }}</p>
                <p class="mt-1 text-xs text-gray-400">Transferred to your account</p>
            </div>
        </div>

        {{-- Fee Breakdown --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Fee Breakdown</h3>
            </div>
            <div class="p-5">
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Gross Donations</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">RM {{ $amountProcessed }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Stripe Connect Fee</span>
                        <span class="font-medium text-red-600">- RM {{ $stripeFee }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ config('app.name') }} Processing Fee ({{ $processingFeeRate }}%)</span>
                        <span class="font-medium text-amber-600">- RM {{ $processingFee }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
                        <div class="flex items-center justify-between text-sm font-semibold">
                            <span class="text-gray-900 dark:text-gray-100">Net to Organisation</span>
                            <span class="text-emerald-600">RM {{ $netReceived }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Note --}}
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/20">
            <div class="flex items-start gap-3">
                <x-heroicon-o-information-circle class="mt-0.5 size-5 text-blue-600 dark:text-blue-400" />
                <div>
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Auto-Deduction</p>
                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                        The {{ config('app.name') }} processing fee is automatically deducted from your Stripe payouts. 
                        No manual payment is required.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
