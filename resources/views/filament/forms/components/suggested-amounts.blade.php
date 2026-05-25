@php
    $statePath = $getStatePath();
@endphp

<div
    x-data="{
        activeCurrency: 'myr',
        activeTab: 'monthly',
        state: $wire.$entangle('{{ $statePath }}'),
        get currencyData() {
            return this.state?.[this.activeCurrency] || { one_time: [], monthly: [], default_monthly: '' };
        },
        set currencyData(value) {
            this.state = { ...this.state, [this.activeCurrency]: value };
        },
        get oneTimeAmounts() {
            return this.currencyData?.one_time || [];
        },
        set oneTimeAmounts(value) {
            this.currencyData = { ...this.currencyData, one_time: value };
        },
        get monthlyAmounts() {
            return this.currencyData?.monthly || [];
        },
        set monthlyAmounts(value) {
            this.currencyData = { ...this.currencyData, monthly: value };
        },
        get defaultMonthly() {
            return this.currencyData?.default_monthly || '';
        },
        set defaultMonthly(value) {
            this.currencyData = { ...this.currencyData, default_monthly: value };
        },
        get currentAmounts() {
            return this.activeTab === 'one-time' ? this.oneTimeAmounts : this.monthlyAmounts;
        },
        set currentAmounts(value) {
            if (this.activeTab === 'one-time') {
                this.oneTimeAmounts = value;
            } else {
                this.monthlyAmounts = value;
            }
        },
        updateAmount(index, value) {
            const amounts = [...this.currentAmounts];
            if (amounts[index]) {
                amounts[index].amount = value.replace(/[^0-9.]/g, '');
                this.currentAmounts = amounts;
            }
        },
        addAmount() {
            const amounts = [...this.currentAmounts, { amount: '', label: '' }];
            this.currentAmounts = amounts;
        },
        removeAmount(index) {
            const amounts = [...this.currentAmounts];
            amounts.splice(index, 1);
            this.currentAmounts = amounts;
        },
        symbolFor(currency) {
            return currency === 'myr' ? 'RM' : (currency === 'usd' ? '$' : 'S$');
        }
    }"
    class="suggested-amounts-component"
>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm ring-1 ring-zinc-950/5 dark:border-white/10 dark:bg-zinc-900 dark:ring-white/10">
        <div class="border-b border-zinc-200 bg-zinc-50/80 px-4 py-5 dark:border-white/10 dark:bg-white/5 sm:px-6">
            <div class="space-y-4">
                <div class="mx-auto max-w-lg space-y-1 text-center">
                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">Pra-set kekerapan & mata wang</p>
                    <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                        Tetapkan butang derma untuk setiap mata wang yang diterima.
                    </p>
                </div>

                <div class="mx-auto grid w-full max-w-sm grid-cols-3 gap-1 rounded-lg bg-zinc-200/70 p-1 dark:bg-zinc-800" role="tablist" aria-label="Currency">
                    @foreach (['myr' => 'RM (MYR)', 'usd' => '$ (USD)', 'sgd' => 'S$ (SGD)'] as $code => $label)
                        <button
                            type="button" role="tab"
                            @click="activeCurrency = '{{ $code }}'"
                            :aria-selected="activeCurrency === '{{ $code }}'"
                            :class="activeCurrency === '{{ $code }}' ? 'bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white'"
                            class="rounded-md px-4 py-2 text-sm font-semibold transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="mx-auto grid w-full max-w-xs grid-cols-2 gap-1 rounded-lg bg-zinc-200/70 p-1 dark:bg-zinc-800" role="tablist" aria-label="Suggested amount frequency">
                    <button
                        type="button" role="tab"
                        @click="activeTab = 'one-time'"
                        :aria-selected="activeTab === 'one-time'"
                        :class="activeTab === 'one-time' ? 'bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white'"
                        class="rounded-md px-4 py-2 text-sm font-semibold transition"
                    >Sekali sahaja</button>
                    <button
                        type="button" role="tab"
                        @click="activeTab = 'monthly'"
                        :aria-selected="activeTab === 'monthly'"
                        :class="activeTab === 'monthly' ? 'bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white'"
                        class="rounded-md px-4 py-2 text-sm font-semibold transition"
                    >Bulanan</button>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-4 sm:p-6">
            <div class="mx-auto max-w-xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-zinc-950 dark:text-white">Jumlah pra-set</h4>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400" x-text="activeTab === 'monthly' ? 'Dipaparkan apabila penyokong memilih derma bulanan.' : 'Dipaparkan apabila penyokong memilih derma satu masa.'"></p>
                    </div>
                    <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400" x-text="(activeTab === 'one-time' ? oneTimeAmounts.length : monthlyAmounts.length) + ' pilihan'"></span>
                </div>

                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                    <template x-for="(amount, index) in currentAmounts" :key="`${activeCurrency}-${activeTab}-${index}`">
                        <div class="group relative rounded-lg border border-zinc-200 bg-zinc-50/60 p-3 transition hover:border-zinc-300 focus-within:border-primary-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-white/10 dark:bg-white/5 dark:hover:border-white/20 dark:focus-within:bg-zinc-950">
                            <button
                                type="button"
                                @click="removeAmount(index)"
                                class="absolute -right-1.5 -top-1.5 hidden size-5 items-center justify-center rounded-full bg-red-500 text-white shadow-sm transition hover:bg-red-600 group-hover:flex dark:bg-red-600 dark:hover:bg-red-500"
                                aria-label="Buang jumlah"
                            >
                                <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>

                            <span class="mb-2 block text-xs font-medium text-zinc-500 dark:text-zinc-400" x-text="'Pilihan ' + (index + 1)"></span>
                            <span class="flex min-h-11 items-center rounded-md border border-zinc-200 bg-white shadow-xs transition group-focus-within:border-primary-500 dark:border-white/10 dark:bg-zinc-900">
                                <span class="flex h-full items-center border-r border-zinc-200 px-3 text-sm font-semibold text-zinc-500 dark:border-white/10 dark:text-zinc-400">
                                    <span x-text="symbolFor(activeCurrency)"></span>
                                </span>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    :value="amount.amount || ''"
                                    @input="updateAmount(index, $event.target.value)"
                                    :aria-label="'Suggested amount option ' + (index + 1)"
                                    placeholder="0"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-base font-semibold text-zinc-950 placeholder-zinc-400 outline-none focus:ring-0 dark:text-white dark:placeholder-zinc-500"
                                >
                            </span>
                        </div>
                    </template>
                </div>

                <button
                    type="button"
                    @click="addAmount()"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-300 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-primary-400 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-white/20 dark:text-zinc-400 dark:hover:border-primary-500 dark:hover:text-primary-400"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah jumlah
                </button>
            </div>

            <div
                class="mx-auto max-w-xl rounded-lg border border-primary-200 bg-primary-50/70 p-4 dark:border-primary-500/20 dark:bg-primary-500/10"
                x-show="activeTab === 'monthly'"
                x-cloak
            >
                <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div class="space-y-1">
                        <label class="text-sm font-semibold text-zinc-950 dark:text-white" :for="'suggested-amounts-default-monthly-' + activeCurrency">
                            Bulanan lalai
                        </label>
                        <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                            Penderma lihat jumlah ini sebagai pra-pilih untuk derma bulanan.
                        </p>
                    </div>
                    <div class="flex min-h-11 w-full items-center rounded-md border border-primary-200 bg-white shadow-xs focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-primary-500/30 dark:bg-zinc-950 lg:w-44">
                        <span class="flex h-full items-center border-r border-primary-200 px-3 text-sm font-semibold text-primary-700 dark:border-primary-500/30 dark:text-primary-300">
                            <span x-text="symbolFor(activeCurrency)"></span>
                        </span>
                        <input
                            :id="'suggested-amounts-default-monthly-' + activeCurrency"
                            type="text"
                            inputmode="numeric"
                            :value="defaultMonthly || ''"
                            @input="defaultMonthly = $event.target.value.replace(/[^0-9.]/g, '')"
                            placeholder="0"
                            class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-base font-semibold text-zinc-950 placeholder-zinc-400 outline-none focus:ring-0 dark:text-white dark:placeholder-zinc-500"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
