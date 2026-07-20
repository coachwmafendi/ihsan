{{-- resources/views/livewire/app/payouts.blade.php --}}
<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Payouts</h1>
            <p class="mt-1 text-sm text-slate-500">Track your Stripe Connect transfers to your bank account.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.card>
            <div class="text-sm font-medium text-slate-500">Paid This Month</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">MYR {{ number_format($this->summary['paid_this_month'], 2) }}</div>
        </x-ui.card>

        <x-ui.card>
            <div class="text-sm font-medium text-slate-500">Pending</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">MYR {{ number_format($this->summary['pending'], 2) }}</div>
        </x-ui.card>

        <x-ui.card class="bg-emerald-50 ring-emerald-200">
            <div class="text-sm font-medium text-emerald-700">Next Expected</div>
            <div class="mt-2 text-2xl font-bold text-emerald-900">MYR {{ number_format($this->summary['next_expected'], 2) }}</div>
            @if ($this->summary['next_expected_at'])
                <div class="mt-1 text-xs text-emerald-600">{{ $this->summary['next_expected_at'] }}</div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Payout History">
        @if ($this->payouts->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-slate-500">
                No payouts found. Payouts will appear once Stripe processes your first transfer.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Amount (MYR)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Bank Account</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($this->payouts as $payout)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900">{{ $payout->arrival_date?->format('j M Y') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900">{{ ucfirst(str_replace('_', ' ', $payout->status)) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-900">{{ number_format($payout->amount / 100, 2) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900">
                                    {{ $payout->bank_name ?? 'Bank' }} ****{{ $payout->bank_account_last4 ?? '----' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
