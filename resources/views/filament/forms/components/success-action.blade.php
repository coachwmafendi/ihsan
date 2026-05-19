<div
    x-data="{
        selected: @js($get('success_action') ?? 'message'),
        options: [
            { value: 'message', label: 'Show Success Message', description: 'Display a thank you message on the same page.', icon: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
            { value: 'redirect', label: 'Redirect to URL', description: 'Send donor to a custom thank you page.', icon: 'M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25' },
        ],
        select(value) {
            this.selected = value;
            $wire.set('data.config.success_action', value);
        }
    }"
    class="col-span-full"
>
    <p class="mb-3 text-xs font-medium text-zinc-500">What happens after a successful donation?</p>
    <div class="grid gap-3 sm:grid-cols-2">
        <template x-for="option in options" :key="option.value">
            <button
                type="button"
                x-on:click="select(option.value)"
                class="flex items-start gap-3 rounded-xl border p-4 text-left transition"
                :class="selected === option.value ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-zinc-200 bg-white hover:border-zinc-300 hover:bg-zinc-50'"
            >
                <div class="mt-0.5 flex shrink-0 items-center justify-center rounded-lg p-1.5"
                     :class="selected === option.value ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-500'">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="option.icon" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold" :class="selected === option.value ? 'text-emerald-900' : 'text-zinc-900'" x-text="option.label"></p>
                    <p class="mt-0.5 text-xs" :class="selected === option.value ? 'text-emerald-700' : 'text-zinc-500'" x-text="option.description"></p>
                </div>
            </button>
        </template>
    </div>
</div>
