{{-- resources/views/livewire/app/reports/monthly-donations.blade.php --}}
<div class="space-y-8">
    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Report</h1>
            <p class="mt-1 text-sm text-slate-500">Review donation collections for your organization</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('app.reports.monthly-donations.download', ['format' => 'csv', 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                Download CSV
            </a>
            <a href="{{ route('app.reports.monthly-donations.download', ['format' => 'pdf', 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]) }}" class="inline-flex items-center justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                Download PDF
            </a>
        </div>
    </div>

    {{-- Period Selector --}}
    <x-ui.card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700">Period</label>
                <div class="mt-1 inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                    <button
                        wire:click="$set('customRange', false)"
                        type="button"
                        class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors {{ ! $customRange ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        Monthly
                    </button>
                    <button
                        wire:click="$set('customRange', true)"
                        type="button"
                        class="rounded-md px-4 py-1.5 text-sm font-medium transition-colors {{ $customRange ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        Custom Range
                    </button>
                </div>
            </div>

            @if (! $customRange)
                <div class="sm:w-48">
                    <label class="block text-sm font-medium text-slate-700">Month</label>
                    <x-ui.select wire:model.live="selectedMonth" class="mt-1 block w-full">
                        @foreach ($this->availableMonths as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </x-ui.select>
                </div>
            @else
                <div>
                    <label for="date-from" class="block text-sm font-medium text-slate-700">Date from</label>
                    <input
                        id="date-from"
                        type="date"
                        wire:model.live="dateFrom"
                        class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                </div>
                <div>
                    <label for="date-to" class="block text-sm font-medium text-slate-700">Date to</label>
                    <input
                        id="date-to"
                        type="date"
                        wire:model.live="dateTo"
                        class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                </div>
            @endif
        </div>
    </x-ui.card>

    {{-- Summary Cards --}}
    <div class="space-y-2">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-ui.card>
                <div class="text-sm font-medium text-slate-500">Total Gross</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">MYR {{ number_format($this->summary['total_gross'], 2) }}</div>
            </x-ui.card>

            <x-ui.card>
                <div class="text-sm font-medium text-slate-500">Processing Fee</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">MYR {{ number_format($this->summary['processing_fee'], 2) }}</div>
            </x-ui.card>

            <x-ui.card class="bg-emerald-50 ring-emerald-200">
                <div class="text-sm font-medium text-emerald-700">Net Received</div>
                <div class="mt-2 text-2xl font-bold text-emerald-900">MYR {{ number_format($this->summary['net_received'], 2) }}</div>
            </x-ui.card>

            <x-ui.card>
                <div class="text-sm font-medium text-slate-500">Total Donations</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($this->summary['total_donations']) }}</div>
            </x-ui.card>

            <x-ui.card>
                <div class="text-sm font-medium text-slate-500">Unique Donors</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($this->summary['unique_donors']) }}</div>
            </x-ui.card>
        </div>

        @if ($this->summary['has_approximations'])
            <p class="text-xs text-amber-600">
                * Non-MYR donations are converted using available exchange rates; totals may be approximate.
            </p>
        @endif
    </div>

    {{-- Campaign Breakdown --}}
    <x-ui.card title="Breakdown by Campaign">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Campaign</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Donations</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Gross (MYR)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Processing Fee (MYR)</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Net (MYR)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($this->campaignBreakdown as $campaign)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ $campaign->title }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-900">{{ number_format($campaign->donations_count) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-900">{{ number_format((float) $campaign->gross_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-900">{{ number_format((float) $campaign->processing_fee, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-900">{{ number_format((float) $campaign->net_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500">No donations found for the selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
