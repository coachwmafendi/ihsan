<x-filament::page>
<div class="space-y-6">
    <x-ui.page-header
        title="Billing Overview"
        subtitle="View your monthly donation volume and fees."
    />

    {{-- Month Selector --}}
    <x-filament::section>
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Select Period</p>
            <div class="w-52">
                <div class="relative">
                    <select
                        wire:model.live="selectedMonth"
                        class="block w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-10 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    >
                        @foreach ($availableMonths as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-chevron-down class="size-4" />
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card
            label="Amount Processed"
            value="RM {{ number_format($amountProcessed, 2) }}"
            subtext="{{ $totalTransactions }} transactions (converted to MYR)"
        />

        <x-ui.stat-card
            label="Donor-Fee Coverage"
            value="+ RM {{ number_format($donorFeeCovered, 2) }}"
            subtext="Donors covering processing fees"
        />

        <x-ui.stat-card
            label="Total Fees"
            value="- RM {{ number_format($stripeFee + $processingFee, 2) }}"
            subtext="Stripe + {{ config('app.name') }} fee"
        />

        <x-ui.stat-card
            label="Net Received"
            value="RM {{ number_format($netReceived, 2) }}"
            subtext="Transferred to your account"
        />
    </div>

    {{-- Fee Breakdown --}}
    <x-filament::section icon="heroicon-o-receipt-percent">
        <x-slot name="heading">Fee Breakdown</x-slot>
        <div class="space-y-3">
            <x-ui.data-row label="Amount Processed (MYR)">
                RM {{ number_format($amountProcessed, 2) }}
            </x-ui.data-row>
            <x-ui.data-row label="Donor-Fee Coverage">
                <span class="text-emerald-600">+ RM {{ number_format($donorFeeCovered, 2) }}</span>
            </x-ui.data-row>
            <x-ui.data-row label="Stripe Connect Fee">
                <span class="text-red-600">- RM {{ number_format($stripeFee, 2) }}</span>
            </x-ui.data-row>
            <x-ui.data-row label="{{ config('app.name') }} Processing Fee ({{ $processingFeeRate }}%)">
                <span class="text-amber-600">- RM {{ number_format($processingFee, 2) }}</span>
            </x-ui.data-row>

            <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
                <x-ui.data-row label="Net to Organisation">
                    <span class="font-semibold text-emerald-600">RM {{ number_format($netReceived, 2) }}</span>
                </x-ui.data-row>
            </div>
        </div>
    </x-filament::section>

    {{-- Info Note --}}
    <x-filament::section collapsible collapsed>
        <x-slot name="heading">How Fees Work</x-slot>
        <div class="space-y-3">
            <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                The {{ config('app.name') }} processing fee of {{ $processingFeeRate }}% is automatically deducted from your Stripe payouts.
                No manual payment is required.
            </p>
            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <li>Stripe Connect processes donations on your behalf</li>
                <li>Fees are deducted before funds reach your bank account</li>
                <li>Donor-fee coverage reduces your effective rate</li>
                <li>Monthly invoices are generated on the 1st of each month</li>
            </ul>
        </div>
    </x-filament::section>
</div>
</x-filament::page>
