<x-filament-panels::page>
    <div
        class="ihsan-admin-page"
        x-data="{
            advancedOpen: false,
            period: '',
            status: '',
            type: '',
            organization: '',
            dateFrom: '',
            dateTo: '',
            minAmount: '',
            maxAmount: '',
            paymentMethod: null,
            isAnonymous: false,

            apply() {
                $wire.applyFilters(
                    this.period,
                    this.status,
                    this.type,
                    this.organization,
                    this.dateFrom,
                    this.dateTo,
                    this.minAmount,
                    this.maxAmount,
                    this.isAnonymous,
                    this.paymentMethod,
                );
            },

            clear() {
                this.period = '';
                this.status = '';
                this.type = '';
                this.organization = '';
                this.dateFrom = '';
                this.dateTo = '';
                this.minAmount = '';
                this.maxAmount = '';
                this.paymentMethod = null;
                this.isAnonymous = false;
                $wire.clearAllFilters();
            },
        }"
    >
        <div class="mb-6 grid gap-4 md:grid-cols-3">
            <x-admin.metric-card
                icon="heroicon-o-banknotes"
                label="Total Amount"
                :value="'MYR '.number_format($this->totals['amount'], 2)"
            />
            <x-admin.metric-card
                icon="heroicon-o-receipt-percent"
                label="Total Processing Fee"
                :value="'MYR '.number_format($this->totals['fee'], 2)"
            />
            <x-admin.metric-card
                icon="heroicon-o-arrow-trending-down"
                label="Total Org Receives"
                :value="'MYR '.number_format($this->totals['org_receives'], 2)"
            />
        </div>

        <div class="mb-3 flex flex-wrap items-center gap-2">
            <select x-model="period" class="rounded-lg border border-stone-200 bg-white px-3 py-2 pr-8 text-sm text-stone-700 shadow-sm transition-colors focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-stone-500 dark:focus:ring-stone-700">
                <option value="">Period: All time</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="7_days">Last 7 days</option>
                <option value="14_days">Last 14 days</option>
                <option value="30_days">Last 30 days</option>
                <option value="this_month">This month</option>
                <option value="last_month">Last month</option>
            </select>

            <select x-model="status" class="rounded-lg border border-stone-200 bg-white px-3 py-2 pr-8 text-sm text-stone-700 shadow-sm transition-colors focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-stone-500 dark:focus:ring-stone-700">
                <option value="">Status: All</option>
                @foreach (App\Enums\DonationStatus::cases() as $case)
                    <option value="{{ $case->value }}">{{ str($case->value)->headline()->toString() }}</option>
                @endforeach
            </select>

            <select x-model="type" class="rounded-lg border border-stone-200 bg-white px-3 py-2 pr-8 text-sm text-stone-700 shadow-sm transition-colors focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-stone-500 dark:focus:ring-stone-700">
                <option value="">Type: All</option>
                @foreach (App\Enums\DonationType::cases() as $case)
                    <option value="{{ $case->value }}">{{ str($case->value)->headline()->toString() }}</option>
                @endforeach
            </select>

            <select x-model="organization" class="max-w-48 rounded-lg border border-stone-200 bg-white px-3 py-2 pr-8 text-sm text-stone-700 shadow-sm transition-colors focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-stone-500 dark:focus:ring-stone-700">
                <option value="">Organization: All</option>
                @foreach ($this->organizationOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-3">
            <button
                @click="advancedOpen = !advancedOpen"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white/70 px-3 py-1.5 text-sm font-medium text-stone-500 shadow-sm transition-all hover:border-stone-300 hover:bg-white hover:text-stone-700 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:bg-stone-900 dark:hover:text-white"
            >
                <x-dynamic-component component="heroicon-o-funnel" class="size-4" />
                <span>More filters</span>
                <x-dynamic-component
                    :component="'heroicon-o-chevron-down'"
                    class="size-3.5 transition-transform"
                    x-bind:class="advancedOpen ? 'rotate-0' : '-rotate-90'"
                />
            </button>

            <button
                @click="apply()"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-stone-800 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-stone-700 dark:bg-white dark:text-stone-900 dark:hover:bg-stone-200"
            >
                Apply Filters
            </button>

            <button
                @click="clear()"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white/70 px-3 py-1.5 text-sm font-medium text-stone-500 shadow-sm transition-all hover:border-stone-300 hover:bg-white hover:text-stone-700 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-400 dark:hover:border-stone-600 dark:hover:bg-stone-900 dark:hover:text-white"
            >
                Clear filters
            </button>
        </div>

        <div x-show="advancedOpen" x-transition.duration.200ms class="mb-6">
            <div class="divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white shadow-sm dark:divide-stone-800 dark:border-stone-700 dark:bg-stone-900">
                <div class="flex flex-wrap items-end gap-4 px-5 py-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium tracking-wide text-stone-500 uppercase dark:text-stone-400">Date from</label>
                        <input
                            type="date"
                            x-model="dateFrom"
                            class="block w-full min-w-36 rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm transition-all focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-white dark:focus:border-stone-500 dark:focus:ring-stone-700"
                        >
                    </div>
                    <div class="hidden self-end pb-[9px] text-stone-300 sm:block dark:text-stone-600">—</div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium tracking-wide text-stone-500 uppercase dark:text-stone-400">Date to</label>
                        <input
                            type="date"
                            x-model="dateTo"
                            class="block w-full min-w-36 rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm transition-all focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-white dark:focus:border-stone-500 dark:focus:ring-stone-700"
                        >
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-x-6 gap-y-4 px-5 py-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium tracking-wide text-stone-500 uppercase dark:text-stone-400">Amount (MYR)</label>
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    x-model="minAmount"
                                    placeholder="Min"
                                    class="block w-28 rounded-lg border border-stone-200 bg-white px-2.5 py-2 text-sm text-stone-900 shadow-sm transition-all placeholder:text-stone-400 focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-white dark:placeholder:text-stone-500 dark:focus:border-stone-500 dark:focus:ring-stone-700"
                                >
                            </div>
                            <span class="text-xs text-stone-400 dark:text-stone-500">to</span>
                            <div class="relative">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    x-model="maxAmount"
                                    placeholder="Max"
                                    class="block w-28 rounded-lg border border-stone-200 bg-white px-2.5 py-2 text-sm text-stone-900 shadow-sm transition-all placeholder:text-stone-400 focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-white dark:placeholder:text-stone-500 dark:focus:border-stone-500 dark:focus:ring-stone-700"
                                >
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium tracking-wide text-stone-500 uppercase dark:text-stone-400">Payment</label>
                        <select
                            x-model="paymentMethod"
                            class="block w-36 rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm transition-all focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-white dark:focus:border-stone-500 dark:focus:ring-stone-700"
                        >
                            <option value="">All</option>
                            <option value="visa">Visa</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="amex">Amex</option>
                        </select>
                    </div>

                    <div class="flex items-center pb-0.5">
                        <label class="relative inline-flex cursor-pointer items-center gap-3">
                            <div class="relative">
                                <input type="checkbox" x-model="isAnonymous" class="peer sr-only">
                                <span class="block h-5 w-9 rounded-full border border-stone-300 bg-white transition-colors peer-checked:border-stone-800 peer-checked:bg-stone-800 dark:border-stone-600 dark:bg-stone-700 dark:peer-checked:border-stone-400 dark:peer-checked:bg-stone-400"></span>
                                <span class="absolute left-0.5 top-0.5 size-4 rounded-full border border-stone-300 bg-white transition-all peer-checked:translate-x-full peer-checked:border-white dark:border-stone-500 dark:bg-stone-200 dark:peer-checked:border-stone-800"></span>
                            </div>
                            <span class="text-xs font-medium text-stone-600 dark:text-stone-400">Anonymous only</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
