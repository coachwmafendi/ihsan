{{-- resources/views/livewire/app/billing.blade.php --}}
<div class="space-y-8">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Billing</h1>
            <p class="mt-1 text-sm text-slate-500">Monthly transaction breakdown for your organisation</p>
        </div>
        <div>
            <select wire:model.live="selectedMonth" class="block rounded-lg border-slate-300 bg-white px-4 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($availableMonths as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.card>
            <div class="text-sm font-medium text-slate-500">Amount Processed</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">RM {{ number_format($amountProcessed, 2) }}</div>
        </x-ui.card>

        <x-ui.card>
            <div class="text-sm font-medium text-slate-500">Donor Fee Covered</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">RM {{ number_format($donorFeeCovered, 2) }}</div>
        </x-ui.card>

        <x-ui.card>
            <div class="text-sm font-medium text-slate-500">Stripe Fee</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">RM {{ number_format($stripeFee, 2) }}</div>
        </x-ui.card>

        <x-ui.card>
            <div class="text-sm font-medium text-slate-500">
                Processing Fee ({{ $processingFeeRate }}%)
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-900">RM {{ number_format($processingFee, 2) }}</div>
        </x-ui.card>

        <x-ui.card>
            <div class="text-sm font-medium text-slate-500">Total Transactions</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $totalTransactions }}</div>
        </x-ui.card>

        <x-ui.card class="bg-emerald-50 ring-emerald-200">
            <div class="text-sm font-medium text-emerald-700">Net Received</div>
            <div class="mt-2 text-2xl font-bold text-emerald-900">RM {{ number_format($netReceived, 2) }}</div>
        </x-ui.card>
    </div>

    {{-- Breakdown Table --}}
    <x-ui.card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Description</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Amount (RM)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    <tr>
                        <td class="px-6 py-4 text-sm text-slate-900">Gross Donations</td>
                        <td class="px-6 py-4 text-right text-sm text-slate-900">{{ number_format($amountProcessed, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-slate-900">Donor Fee Covered</td>
                        <td class="px-6 py-4 text-right text-sm text-slate-900">{{ number_format($donorFeeCovered, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-slate-600">Stripe Fee</td>
                        <td class="px-6 py-4 text-right text-sm text-red-600">-{{ number_format($stripeFee, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            Processing Fee ({{ $processingFeeRate }}% collected by us)
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-red-600">-{{ number_format($processingFee, 2) }}</td>
                    </tr>
                    <tr class="border-t-2 border-slate-100 bg-slate-50">
                        <td class="px-6 py-4 text-sm font-bold text-slate-900">Net Received</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-slate-900">{{ number_format($netReceived, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
