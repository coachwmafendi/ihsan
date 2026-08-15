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
        <div class="mb-6 flex flex-wrap items-center gap-3">
            {{-- Date filter chip --}}
            <div
                class="relative"
                x-data="{
                    open: false,
                    op: $wire.dateOperator,
                    value: $wire.dateValue,
                    unit: $wire.dateUnit,
                    apply() {
                        $wire.set('dateOperator', this.op);
                        $wire.set('dateValue', this.value);
                        $wire.set('dateUnit', this.unit);
                        $wire.applyDateFilter();
                        this.open = false;
                    }
                }"
                @click.outside="open = false"
            >
                <button
                    type="button"
                    @click="open = !open"
                    @class([
                        'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                        'border-teal-300 bg-teal-50 text-teal-700 hover:bg-teal-100' => $this->hasDateFilter(),
                        'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => ! $this->hasDateFilter(),
                    ])
                >
                    @if ($this->hasDateFilter())
                        <x-heroicon-o-x-mark
                            class="size-3.5"
                            wire:click.stop="clearDateFilter"
                        />
                        <span>Date | {{ $this->dateChipLabel() }}</span>
                        <x-heroicon-o-chevron-down class="size-3.5" />
                    @else
                        <x-heroicon-o-plus class="size-3.5" />
                        <span>Date</span>
                    @endif
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute left-0 top-full z-50 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-4 shadow-xl"
                    style="display: none;"
                >
                    <div class="text-sm font-semibold text-slate-900">Filter by: Date</div>

                    <select x-model="op" class="mt-3 block w-full rounded-lg border-slate-300 bg-white py-2 pl-3 pr-10 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        <option value="last">is in the last</option>
                        <option value="equal">is equal to</option>
                        <option value="between">is between</option>
                        <option value="on_or_after">is on or after</option>
                        <option value="before_or_on">is before or on</option>
                    </select>

                    <div x-show="op === 'last'" class="mt-3 flex items-center gap-2">
                        <input x-model.number="value" type="number" min="1" class="block w-20 rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        <select x-model="unit" class="block rounded-lg border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                            <option value="days">days</option>
                            <option value="months">months</option>
                        </select>
                    </div>

                    <div x-show="op === 'equal'" class="mt-3">
                        <x-ui.date-picker wire-model="dateSingle" inline />
                    </div>

                    <div x-show="op === 'between'" class="mt-3 w-[28rem]">
                        <x-ui.date-range-calendar
                            wire:from="pendingDateFrom"
                            wire:to="pendingDateTo"
                            :initial-from="$pendingDateFrom"
                            :initial-to="$pendingDateTo"
                            inline
                        />
                    </div>

                    <div x-show="op === 'on_or_after'" class="mt-3">
                        <x-ui.date-picker wire-model="dateSingle" inline />
                    </div>

                    <div x-show="op === 'before_or_on'" class="mt-3">
                        <x-ui.date-picker wire-model="dateSingle" inline />
                    </div>

                    <button
                        type="button"
                        @click="apply()"
                        class="mt-4 w-full rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    >Apply</button>
                </div>
            </div>

            {{-- Amount filter chip --}}
            <div
                class="relative"
                x-data="{
                    open: false,
                    op: $wire.amountOperator,
                    value: $wire.amountValue,
                    min: $wire.amountMin,
                    max: $wire.amountMax,
                    apply() {
                        $wire.set('amountOperator', this.op);
                        $wire.set('amountValue', this.value);
                        $wire.set('amountMin', this.min);
                        $wire.set('amountMax', this.max);
                        $wire.applyAmountFilter();
                        this.open = false;
                    }
                }"
                @click.outside="open = false"
            >
                <button
                    type="button"
                    @click="open = !open"
                    @class([
                        'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                        'border-teal-300 bg-teal-50 text-teal-700 hover:bg-teal-100' => $this->hasAmountFilter(),
                        'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => ! $this->hasAmountFilter(),
                    ])
                >
                    @if ($this->hasAmountFilter())
                        <x-heroicon-o-x-mark
                            class="size-3.5"
                            wire:click.stop="clearAmountFilter"
                        />
                        <span>Amount | {{ $this->amountChipLabel() }}</span>
                        <x-heroicon-o-chevron-down class="size-3.5" />
                    @else
                        <x-heroicon-o-plus class="size-3.5" />
                        <span>Amount</span>
                    @endif
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute left-0 top-full z-50 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-4 shadow-xl"
                    style="display: none;"
                >
                    <div class="text-sm font-semibold text-slate-900">Filter by: Amount</div>

                    <select x-model="op" class="mt-3 block w-full rounded-lg border-slate-300 bg-white py-2 pl-3 pr-10 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        <option value="equal">is equal to</option>
                        <option value="between">is between</option>
                        <option value="greater_than">is greater than</option>
                        <option value="less_than">is less than</option>
                    </select>

                    <div x-show="op === 'equal'" class="mt-3">
                        <input x-model.number="value" type="number" step="0.01" min="0" placeholder="MYR 0.00" class="block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div x-show="op === 'between'" class="mt-3 space-y-2">
                        <input x-model.number="min" type="number" step="0.01" min="0" placeholder="Minimum" class="block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        <input x-model.number="max" type="number" step="0.01" min="0" placeholder="Maximum" class="block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <div x-show="op === 'greater_than' || op === 'less_than'" class="mt-3">
                        <input x-model.number="value" type="number" step="0.01" min="0" placeholder="MYR 0.00" class="block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>

                    <button
                        type="button"
                        @click="apply()"
                        class="mt-4 w-full rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    >Apply</button>
                </div>
            </div>

            {{-- Status filter chip --}}
            <div
                class="relative"
                x-data="{
                    open: false,
                    status: $wire.statusFilter,
                    apply() {
                        $wire.set('statusFilter', this.status);
                        $wire.applyStatusFilter();
                        this.open = false;
                    }
                }"
                @click.outside="open = false"
            >
                <button
                    type="button"
                    @click="open = !open"
                    @class([
                        'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                        'border-teal-300 bg-teal-50 text-teal-700 hover:bg-teal-100' => $this->hasStatusFilter(),
                        'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => ! $this->hasStatusFilter(),
                    ])
                >
                    @if ($this->hasStatusFilter())
                        <x-heroicon-o-x-mark
                            class="size-3.5"
                            wire:click.stop="clearStatusFilter"
                        />
                        <span>Status | {{ $this->statusChipLabel() }}</span>
                        <x-heroicon-o-chevron-down class="size-3.5" />
                    @else
                        <x-heroicon-o-plus class="size-3.5" />
                        <span>Status</span>
                    @endif
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute left-0 top-full z-50 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-4 shadow-xl"
                    style="display: none;"
                >
                    <div class="text-sm font-semibold text-slate-900">Filter by: Status</div>

                    <select x-model="status" class="mt-3 block w-full rounded-lg border-slate-300 bg-white py-2 pl-3 pr-10 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        <option value="">All statuses</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="in_transit">In transit</option>
                        <option value="failed">Failed</option>
                        <option value="canceled">Canceled</option>
                    </select>

                    <button
                        type="button"
                        @click="apply()"
                        class="mt-4 w-full rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    >Apply</button>
                </div>
            </div>

            @if ($this->hasAnyFilter())
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="text-sm font-medium text-teal-600 hover:text-teal-700"
                >Clear filters</button>
            @endif
        </div>

        @if ($this->payouts->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-slate-500">
                No payouts found for the selected filters.
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
                                    @if ($payout->bank_name || $payout->bank_account_last4)
                                        {{ $payout->bank_name ?? 'Bank' }} ****{{ $payout->bank_account_last4 ?? '----' }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</div>
