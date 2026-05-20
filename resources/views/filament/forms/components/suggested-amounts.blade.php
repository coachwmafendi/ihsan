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
        state: $wire.$entangle('{{ $statePath }}'),
        get amounts() {
            return this.state?.one_time || [];
        },
        set amounts(value) {
            this.state = { ...this.state, one_time: value };
        },
        get defaultMonthly() {
            return this.state?.default_monthly || '';
        },
        set defaultMonthly(value) {
            this.state = { ...this.state, default_monthly: value };
        },
        updateAmount(index, value) {
            const amounts = [...this.amounts];
            if (amounts[index]) {
                amounts[index].amount = value;
                this.amounts = amounts;
            }
        }
    }"
    class="suggested-amounts-component space-y-6"
>
    <p class="text-sm text-zinc-600">
        Fill in suggested donation amount presets for our AI to adjust them for each supporter.
    </p>

    <div>
        <h4 class="mb-4 text-base font-semibold text-zinc-900">Suggested donation amount presets</h4>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <template x-for="(amount, index) in amounts" :key="index">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-zinc-400">RM</span>
                    <input
                        type="number"
                        :value="amount.amount"
                        @input="updateAmount(index, $event.target.value)"
                        class="w-full rounded-lg border border-zinc-300 py-3 pl-10 pr-4 text-base font-medium text-zinc-900 placeholder-zinc-400 focus:border-primary-500 focus:ring-primary-500"
                    >
                </div>
            </template>
        </div>
    </div>

    <div class="pt-4">
        <label class="mb-3 block text-base font-semibold text-zinc-900">Default monthly suggested amount</label>
        <div class="relative w-40">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-zinc-400">RM</span>
            <input
                type="number"
                :value="defaultMonthly"
                @input="defaultMonthly = $event.target.value"
                class="w-full rounded-lg border border-zinc-300 py-3 pl-10 pr-4 text-base font-medium text-zinc-900 placeholder-zinc-400 focus:border-primary-500 focus:ring-primary-500"
            >
        </div>
    </div>
</div>
