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
                this.$dispatch('filters-cleared');
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
            <div class="relative">
                <select x-model="period" class="w-full appearance-none rounded-lg border border-stone-200 bg-white py-2 pl-3 pr-10 text-sm text-stone-700 shadow-sm transition-colors focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-stone-500 dark:focus:ring-stone-700">
                <option value="">Period: All time</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="7_days">Last 7 days</option>
                <option value="14_days">Last 14 days</option>
                <option value="30_days">Last 30 days</option>
                <option value="this_month">This month</option>
                <option value="last_month">Last month</option>
            </select>
                <x-dynamic-component
                    component="heroicon-m-chevron-down"
                    class="pointer-events-none absolute inset-y-0 right-3 my-auto size-4 text-stone-400"
                />
            </div>

            <div class="relative">
                <select x-model="status" class="w-full appearance-none rounded-lg border border-stone-200 bg-white py-2 pl-3 pr-10 text-sm text-stone-700 shadow-sm transition-colors focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-stone-500 dark:focus:ring-stone-700">
                <option value="">Status: All</option>
                @foreach (App\Enums\DonationStatus::cases() as $case)
                    <option value="{{ $case->value }}">{{ str($case->value)->headline()->toString() }}</option>
                @endforeach
            </select>
                <x-dynamic-component
                    component="heroicon-m-chevron-down"
                    class="pointer-events-none absolute inset-y-0 right-3 my-auto size-4 text-stone-400"
                />
            </div>

            <div class="relative">
                <select x-model="type" class="w-full appearance-none rounded-lg border border-stone-200 bg-white py-2 pl-3 pr-10 text-sm text-stone-700 shadow-sm transition-colors focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-stone-500 dark:focus:ring-stone-700">
                <option value="">Type: All</option>
                @foreach (App\Enums\DonationType::cases() as $case)
                    <option value="{{ $case->value }}">{{ str($case->value)->headline()->toString() }}</option>
                @endforeach
            </select>
                <x-dynamic-component
                    component="heroicon-m-chevron-down"
                    class="pointer-events-none absolute inset-y-0 right-3 my-auto size-4 text-stone-400"
                />
            </div>


<script>
    // A plain global, not Alpine.data(): registering on alpine:init raced
    // Filament's own Alpine boot, and when it lost, nothing on the element
    // initialised and the dropdown rendered as an empty white box.
    window.organizationCombobox = function (options) {
        return {
        selected: '',
        search: '',
        isOpen: false,
        highlighted: 0,

        // {id: name} from the server, as a list the template can walk.
        all: Object.entries(options).map(([id, name]) => ({ id: String(id), name })),

        init() {
            this.search = this.selectedName;
        },

        get matches() {
            const term = this.search.trim().toLowerCase();

            if (term === '') {
                return this.all;
            }

            return this.all.filter((option) => option.name.toLowerCase().includes(term));
        },

        get selectedName() {
            return this.all.find((option) => option.id === this.selected)?.name ?? '';
        },

        open() {
            this.isOpen = true;
            this.highlighted = 0;
        },

        close() {
            this.isOpen = false;
            // Typing without choosing leaves the box showing whatever
            // is actually filtering the table.
            this.search = this.selectedName;
        },

        move(step) {
            if (! this.isOpen) {
                this.open();

                return;
            }

            const count = this.matches.length;

            if (count === 0) {
                return;
            }

            this.highlighted = (this.highlighted + step + count) % count;
        },

        choose(option) {
            if (! option) {
                return;
            }

            this.selected = option.id;
            this.search = option.name;
            this.isOpen = false;
            this.$dispatch('organization-selected', option.id);
        },

        clearSelection() {
            this.reset();
            this.$dispatch('organization-selected', '');
            this.$refs.input.focus();
        },

        // Also called when the page clears every filter at once.
        reset() {
            this.selected = '';
            this.search = '';
            this.isOpen = false;
        },
        };
    };
</script>


            {{-- Typeable organization picker: the list grows with every NGO
                 onboarded, so scrolling a plain select stops scaling. --}}
            <div
                class="relative"
                wire:ignore
                x-data="organizationCombobox(@js($this->organizationOptions))"
                @organization-selected="organization = $event.detail"
                @filters-cleared.window="reset()"
                @click.outside="close()"
                @keydown.escape.stop="close()"
            >
                <input
                    type="text"
                    x-model="search"
                    x-ref="input"
                    @focus="open()"
                    @click="open()"
                    @input="open()"
                    @keydown.arrow-down.prevent="move(1)"
                    @keydown.arrow-up.prevent="move(-1)"
                    @keydown.enter.prevent="choose(matches[highlighted])"
                    @keydown.tab="close()"
                    :placeholder="selectedName || 'Organization: All'"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="organization-combobox-list"
                    :aria-expanded="isOpen ? 'true' : 'false'"
                    aria-label="Filter by organization"
                    class="w-56 rounded-lg border border-stone-200 bg-white py-2 pl-3 pr-10 text-sm text-stone-700 shadow-sm transition-colors focus:border-stone-400 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:focus:border-stone-500 dark:focus:ring-stone-700"
                >

                <button
                    type="button"
                    :class="{ 'hidden': selected === '' && search === '' }"
                    @click="clearSelection()"
                    aria-label="Clear organization filter"
                    class="hidden absolute inset-y-0 right-2 flex items-center text-stone-400 transition-colors hover:text-stone-600 dark:hover:text-stone-200"
                >
                    <x-dynamic-component component="heroicon-o-x-mark" class="size-4" />
                </button>

                <ul
                    :class="{ 'hidden': ! isOpen }"
                    id="organization-combobox-list"
                    role="listbox"
                    class="hidden absolute z-20 mt-1 max-h-64 w-72 overflow-y-auto rounded-lg border border-stone-200 bg-white py-1 shadow-lg dark:border-stone-700 dark:bg-stone-800"
                >
                    <template x-for="(option, index) in matches" :key="option.id">
                        <li
                            role="option"
                            :aria-selected="option.id === selected"
                            @click="choose(option)"
                            @mouseenter="highlighted = index"
                            class="cursor-pointer px-3 py-2 text-sm"
                            :class="index === highlighted
                                ? 'bg-stone-100 text-stone-900 dark:bg-stone-700 dark:text-white'
                                : 'text-stone-700 dark:text-stone-300'"
                            x-text="option.name"
                        ></li>
                    </template>

                    <li
                        x-show="matches.length === 0"
                        class="px-3 py-2 text-sm text-stone-400 dark:text-stone-500"
                    >
                        No organization matches that name.
                    </li>
                </ul>
            </div>
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
