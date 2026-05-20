@php
    $statePath = $getStatePath();
@endphp

<style>
    .suggested-amounts-component input[type="number"]::-webkit-inner-spin-button,
    .suggested-amounts-component input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .suggested-amounts-component input[type="number"] {
        -moz-appearance: textfield;
    }
</style>

<div
    x-data="{
        activeTab: 'monthly',
        state: $wire.$entangle('{{ $statePath }}'),
        get oneTimeAmounts() {
            return this.state?.one_time || [];
        },
        set oneTimeAmounts(value) {
            this.state = { ...this.state, one_time: value };
        },
        get monthlyAmounts() {
            return this.state?.monthly || [];
        },
        set monthlyAmounts(value) {
            this.state = { ...this.state, monthly: value };
        },
        get impactEnabled() {
            return this.state?.impact_enabled || false;
        },
        set impactEnabled(value) {
            this.state = { ...this.state, impact_enabled: value };
        },
        get defaultMonthly() {
            return this.state?.default_monthly || '';
        },
        set defaultMonthly(value) {
            this.state = { ...this.state, default_monthly: value };
        },
        addAmount(type) {
            const amounts = type === 'one-time' ? this.oneTimeAmounts : this.monthlyAmounts;
            amounts.push({ amount: '', label: '' });
            if (type === 'one-time') {
                this.oneTimeAmounts = [...amounts];
            } else {
                this.monthlyAmounts = [...amounts];
            }
        },
        removeAmount(type, index) {
            const amounts = type === 'one-time' ? this.oneTimeAmounts : this.monthlyAmounts;
            amounts.splice(index, 1);
            if (type === 'one-time') {
                this.oneTimeAmounts = [...amounts];
            } else {
                this.monthlyAmounts = [...amounts];
            }
        },
        updateAmount(type, index, field, value) {
            const amounts = type === 'one-time' ? this.oneTimeAmounts : this.monthlyAmounts;
            if (amounts[index]) {
                amounts[index][field] = value;
                if (type === 'one-time') {
                    this.oneTimeAmounts = [...amounts];
                } else {
                    this.monthlyAmounts = [...amounts];
                }
            }
        }
    }"
    class="suggested-amounts-component"
>
    <div class="rounded-xl border border-zinc-200 bg-white p-6">
        <!-- Tabs -->
        <div class="mb-6 flex gap-1 border-b border-zinc-200">
            <button
                type="button"
                @click="activeTab = 'one-time'"
                :class="activeTab === 'one-time' ? 'border-primary-500 text-primary-600' : 'border-transparent text-zinc-500 hover:text-zinc-700'"
                class="border-b-2 px-4 py-2 text-sm font-semibold transition"
            >
                ONE-TIME
            </button>
            <button
                type="button"
                @click="activeTab = 'monthly'"
                :class="activeTab === 'monthly' ? 'border-primary-500 text-primary-600' : 'border-transparent text-zinc-500 hover:text-zinc-700'"
                class="border-b-2 px-4 py-2 text-sm font-semibold transition"
            >
                MONTHLY
            </button>
        </div>

        <!-- Impact Descriptions Toggle -->
        <div class="mb-6 flex items-start gap-3">
            <input
                type="checkbox"
                x-model="impactEnabled"
                class="mt-1 size-4 rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
            >
            <div>
                <label class="text-sm font-medium text-zinc-900">Enable impact descriptions</label>
                <p class="mt-1 text-sm text-zinc-500">
                    Boost supporters' engagement and generosity by telling them how their funds might be used (e.g. "RM30 can buy school supplies for one child").
                </p>
            </div>
        </div>

        <hr class="my-6 border-zinc-200">

        <!-- One-Time Tab Content -->
        <div x-show="activeTab === 'one-time'" x-cloak>
            <p class="mb-4 text-sm text-zinc-600">
                Fill in suggested donation amount presets for one-time donations.
            </p>

            <h4 class="mb-3 text-sm font-semibold text-zinc-900">Suggested donation amount presets</h4>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="(amount, index) in oneTimeAmounts" :key="index">
                    <div class="relative">
                        <input
                            type="number"
                            :value="amount.amount"
                            @input="updateAmount('one-time', index, 'amount', $event.target.value)"
                            placeholder="RM"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                        <button
                            type="button"
                            @click="removeAmount('one-time', index)"
                            class="absolute -right-1.5 -top-1.5 flex size-5 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-zinc-200 text-zinc-400 hover:bg-red-50 hover:text-red-500 hover:ring-red-200"
                            x-show="oneTimeAmounts.length > 1"
                        >
                            <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <button
                type="button"
                @click="addAmount('one-time')"
                class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-600 hover:border-primary-400 hover:text-primary-600"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add amount
            </button>
        </div>

        <!-- Monthly Tab Content -->
        <div x-show="activeTab === 'monthly'" x-cloak>
            <p class="mb-4 text-sm text-zinc-600">
                Fill in suggested donation amount presets for monthly recurring donations.
            </p>

            <h4 class="mb-3 text-sm font-semibold text-zinc-900">Suggested donation amount presets</h4>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <template x-for="(amount, index) in monthlyAmounts" :key="index">
                    <div class="relative">
                        <input
                            type="number"
                            :value="amount.amount"
                            @input="updateAmount('monthly', index, 'amount', $event.target.value)"
                            placeholder="RM"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                        <button
                            type="button"
                            @click="removeAmount('monthly', index)"
                            class="absolute -right-1.5 -top-1.5 flex size-5 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-zinc-200 text-zinc-400 hover:bg-red-50 hover:text-red-500 hover:ring-red-200"
                            x-show="monthlyAmounts.length > 1"
                        >
                            <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <button
                type="button"
                @click="addAmount('monthly')"
                class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-600 hover:border-primary-400 hover:text-primary-600"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add amount
            </button>

            <div class="mt-8 pt-6 border-t border-zinc-200">
                <label class="mb-3 block text-sm font-semibold text-zinc-900">Default monthly suggested amount</label>
                <input
                    type="number"
                    :value="defaultMonthly"
                    @input="defaultMonthly = $event.target.value"
                    placeholder="25"
                    class="w-32 rounded-lg border border-zinc-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:ring-primary-500"
                >
            </div>
        </div>
    </div>
</div>
