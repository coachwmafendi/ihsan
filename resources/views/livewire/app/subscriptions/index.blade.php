{{-- resources/views/livewire/app/subscriptions/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Recurring Plans</h1>
            <p class="mt-1 text-sm text-slate-500">Manage recurring donation plans</p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Total Recurring Plans" value="{{ number_format($this->totalCount) }}" />
    </div>

    {{-- Filter chips --}}
    @php $dateActive = $period !== 'all_time'; $dateLabel = $this->dateChipLabel; @endphp
    <div class="flex flex-wrap items-center gap-2">

        {{-- Date chip --}}
        <div
            class="relative"
            x-data="{
                open: false,
                leftYear: new Date().getFullYear(),
                leftMonth: new Date().getMonth(),
                startDate: @js($dateFrom ?: null),
                endDate:   @js($dateTo   ?: null),
                hovering: null,
                get rightYear()  { return this.leftMonth === 11 ? this.leftYear + 1 : this.leftYear },
                get rightMonth() { return (this.leftMonth + 1) % 12 },
                prevMonth() { if (this.leftMonth === 0) { this.leftMonth = 11; this.leftYear-- } else this.leftMonth-- },
                nextMonth() { if (this.leftMonth === 11) { this.leftMonth = 0; this.leftYear++ } else this.leftMonth++ },
                daysInMonth(y, m)  { return new Date(y, m + 1, 0).getDate() },
                firstWeekday(y, m) { let d = new Date(y, m, 1).getDay(); return d === 0 ? 6 : d - 1 },
                getMonthDays(y, m) {
                    let days = Array(this.firstWeekday(y, m)).fill(null);
                    for (let i = 1; i <= this.daysInMonth(y, m); i++) days.push(i);
                    return days;
                },
                fmt(y, m, d) { return String(y) + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0') },
                monthName(m) { return ['January','February','March','April','May','June','July','August','September','October','November','December'][m] },
                selectDay(dateStr) {
                    if (!this.startDate || (this.startDate && this.endDate)) {
                        this.startDate = dateStr; this.endDate = null;
                    } else {
                        let [s, e] = dateStr < this.startDate ? [dateStr, this.startDate] : [this.startDate, dateStr];
                        this.startDate = s; this.endDate = e;
                        $wire.set('dateFrom', s); $wire.set('dateTo', e); $wire.set('period', 'custom');
                        this.open = false;
                    }
                },
                isStart(d)   { return d === this.startDate },
                isEnd(d)     { return d === this.endDate },
                isInRange(d) {
                    if (!this.startDate) return false;
                    let end = this.endDate || this.hovering;
                    if (!end) return false;
                    let [s, e] = this.startDate <= end ? [this.startDate, end] : [end, this.startDate];
                    return d > s && d < e;
                },
                isToday(d) { return d === new Date().toISOString().slice(0,10) }
            }"
            @click.outside="open = false"
        >
            <button
                @click="open = !open"
                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors"
                @class([
                    'border-teal-600 bg-teal-50 font-medium text-teal-700' => $dateActive,
                    'border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800' => ! $dateActive,
                ])
            >
                <x-heroicon-o-calendar-days class="size-3.5 shrink-0" />
                <span>{{ $dateLabel }}</span>
                @if ($dateActive)
                    <span wire:click.stop="clearDate" class="ml-0.5 cursor-pointer text-teal-500 hover:text-teal-800">
                        <x-heroicon-o-x-mark class="size-3.5" />
                    </span>
                @endif
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="absolute left-0 top-full z-50 mt-2 flex rounded-xl border border-slate-200 bg-white shadow-xl"
                style="display:none"
            >
                <div class="flex gap-8 p-5">
                    @foreach ([['leftYear', 'leftMonth', false], ['rightYear', 'rightMonth', true]] as [$yr, $mo, $isRight])
                    <div class="w-48">
                        <div class="mb-3 flex items-center justify-between">
                            @if (! $isRight)
                                <button @click="prevMonth()" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><x-heroicon-o-chevron-left class="size-4" /></button>
                            @else
                                <div class="size-6"></div>
                            @endif
                            <span class="text-sm font-semibold text-slate-700" x-text="monthName({{ $mo }}) + ' ' + {{ $yr }}"></span>
                            @if ($isRight)
                                <button @click="nextMonth()" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><x-heroicon-o-chevron-right class="size-4" /></button>
                            @else
                                <div class="size-6"></div>
                            @endif
                        </div>
                        <div class="grid grid-cols-7 text-center">
                            @foreach (['Mo','Tu','We','Th','Fr','Sa','Su'] as $wd)
                                <div class="py-1 text-[10px] font-medium uppercase text-slate-400">{{ $wd }}</div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-7">
                            <template x-for="(day, i) in getMonthDays({{ $yr }}, {{ $mo }})" :key="i">
                                <div class="flex items-center justify-center py-0.5">
                                    <template x-if="day === null"><span></span></template>
                                    <template x-if="day !== null">
                                        <button
                                            @click="selectDay(fmt({{ $yr }}, {{ $mo }}, day))"
                                            @mouseover="if (startDate && !endDate) hovering = fmt({{ $yr }}, {{ $mo }}, day)"
                                            @mouseleave="hovering = null"
                                            class="size-7 rounded-full text-xs transition-colors"
                                            :class="{
                                                'bg-teal-600 text-white font-semibold': isStart(fmt({{ $yr }}, {{ $mo }}, day)) || isEnd(fmt({{ $yr }}, {{ $mo }}, day)),
                                                'bg-teal-100 text-teal-800': isInRange(fmt({{ $yr }}, {{ $mo }}, day)),
                                                'ring-1 ring-teal-400 text-teal-700': isToday(fmt({{ $yr }}, {{ $mo }}, day)) && !isStart(fmt({{ $yr }}, {{ $mo }}, day)) && !isEnd(fmt({{ $yr }}, {{ $mo }}, day)),
                                                'text-slate-700 hover:bg-slate-100': !isStart(fmt({{ $yr }}, {{ $mo }}, day)) && !isEnd(fmt({{ $yr }}, {{ $mo }}, day)) && !isInRange(fmt({{ $yr }}, {{ $mo }}, day))
                                            }"
                                            x-text="day"
                                        ></button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-36 border-l border-slate-100 py-3">
                    @foreach ([
                        ['all_time','All'],['today','Today'],['yesterday','Yesterday'],
                        ['7_days','Last 7 days'],['14_days','Last 14 days'],['30_days','Last 30 days'],
                        ['this_week','This week'],['this_month','This month'],['this_year','This year'],
                        ['last_week','Last week'],['last_month','Last month'],['last_year','Last year'],
                    ] as [$val, $lbl])
                        <button
                            wire:click="$set('period', '{{ $val }}')"
                            @click="open = false; startDate = null; endDate = null; $wire.set('dateFrom', ''); $wire.set('dateTo', '')"
                            class="w-full px-4 py-1.5 text-left text-sm transition-colors hover:bg-slate-50 @if ($period === $val) font-semibold text-teal-700 @else text-slate-600 @endif"
                        >{{ $lbl }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Status chip --}}
        <flux:dropdown>
            <button class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors @if($statusFilter) border-teal-600 bg-teal-50 font-medium text-teal-700 @else border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800 @endif">
                @if ($statusFilter) {{ ucfirst(str_replace('_', ' ', $statusFilter)) }} @else Status @endif
                @if ($statusFilter)
                    <span wire:click.stop="$set('statusFilter', '')" class="ml-0.5 cursor-pointer text-teal-500 hover:text-teal-800"><x-heroicon-o-x-mark class="size-3.5" /></span>
                @endif
            </button>
            <flux:menu>
                <flux:menu.item wire:click="$set('statusFilter', '')"          @class(['font-semibold text-teal-700' => ! $statusFilter])>All</flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item wire:click="$set('statusFilter', 'active')"    @class(['font-semibold text-teal-700' => $statusFilter === 'active'])>Active</flux:menu.item>
                <flux:menu.item wire:click="$set('statusFilter', 'paused')"    @class(['font-semibold text-teal-700' => $statusFilter === 'paused'])>Paused</flux:menu.item>
                <flux:menu.item wire:click="$set('statusFilter', 'cancelled')" @class(['font-semibold text-teal-700' => $statusFilter === 'cancelled'])>Cancelled</flux:menu.item>
                <flux:menu.item wire:click="$set('statusFilter', 'past_due')"  @class(['font-semibold text-teal-700' => $statusFilter === 'past_due'])>Past Due</flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        {{-- Campaign chip --}}
        <flux:dropdown>
            <button class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors @if($campaignFilter) border-teal-600 bg-teal-50 font-medium text-teal-700 @else border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800 @endif">
                Campaign
                @if ($campaignFilter)
                    <span wire:click.stop="$set('campaignFilter', '')" class="ml-0.5 cursor-pointer text-teal-500 hover:text-teal-800"><x-heroicon-o-x-mark class="size-3.5" /></span>
                @endif
            </button>
            <flux:menu class="max-h-72 w-56 overflow-y-auto">
                <flux:menu.item wire:click="$set('campaignFilter', '')" @class(['font-semibold text-teal-700' => ! $campaignFilter])>All Campaigns</flux:menu.item>
                <flux:menu.separator />
                @foreach ($this->campaigns as $campaign)
                    <flux:menu.item wire:click="$set('campaignFilter', '{{ $campaign->id }}')" @class(['font-semibold text-teal-700' => $campaignFilter == $campaign->id])>{{ $campaign->title }}</flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>

        {{-- More filters (search) --}}
        <flux:dropdown>
            <button class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors @if($search) border-teal-600 bg-teal-50 font-medium text-teal-700 @else border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800 @endif">
                <x-heroicon-o-plus class="size-3.5" />
                More filters
                @if ($search)
                    <span wire:click.stop="$set('search', '')" class="ml-0.5 cursor-pointer text-teal-500 hover:text-teal-800"><x-heroicon-o-x-mark class="size-3.5" /></span>
                @endif
            </button>
            <flux:menu keep-open class="w-64 p-2">
                <div class="relative px-1 py-1">
                    <x-heroicon-o-magnifying-glass class="absolute left-4 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search supporters..."
                        class="h-9 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    />
                </div>
            </flux:menu>
        </flux:dropdown>

    </div>

    {{-- Subscriptions Table --}}
    <x-ui.card>
        @if ($this->subscriptions->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1">
                                    Create date
                                    @if ($sortField === 'created_at')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('amount')" class="group inline-flex items-center gap-1">
                                    Amount
                                    @if ($sortField === 'amount')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Installments</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('donor')" class="group inline-flex items-center gap-1">
                                    Supporter
                                    @if ($sortField === 'donor')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->subscriptions as $subscription)
                            @php
                                $latestDonation = $subscription->donations->first();
                                $pmBrand = strtolower($latestDonation?->payment_method_brand ?? '');
                                $pmType  = $latestDonation?->payment_method_type ?? '';

                                $isMyr        = strtolower($subscription->currency) === 'myr';
                                $exchangeRate = (float) ($latestDonation?->exchange_rate ?? 1);
                                $myrAmount    = $isMyr
                                    ? (float) $subscription->amount
                                    : round((float) $subscription->amount * $exchangeRate, 2);

                                $totalMyr     = (float) ($subscription->donations_sum_base_amount ?? 0);
                                $isApprox     = ! $isMyr || $totalMyr !== round((float) $subscription->amount * $subscription->payment_count, 2);
                            @endphp
                            <tr
                                class="cursor-pointer transition-colors hover:bg-slate-50"
                                onclick="window.location='{{ route('app.subscriptions.show', $subscription) }}'"
                            >
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ $subscription->created_at->format('M d, Y, g:i A') }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-800">
                                            {{ $isMyr ? '' : '≈ ' }}MYR {{ number_format($myrAmount, 2) }}/{{ $subscription->interval->value }}
                                        </span>
                                        @if (! $isMyr)
                                            <x-ui.tooltip :text="$subscription->currency_symbol.' '.number_format((float) $subscription->amount, 2).'/'.$subscription->interval->value">
                                                <x-heroicon-o-information-circle class="size-3.5 text-slate-400" />
                                            </x-ui.tooltip>
                                        @endif
                                        {{-- Payment method icon --}}
                                        @if ($pmBrand === 'apple_pay' || $pmType === 'apple_pay')
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-600">Pay</span>
                                        @elseif ($pmBrand === 'google_pay' || $pmType === 'google_pay')
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-600">G Pay</span>
                                        @elseif ($pmType === 'card' || filled($pmBrand))
                                            <x-heroicon-o-credit-card class="size-4 text-slate-400" />
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ $subscription->payment_count }}
                                </td>
                                <td class="px-5 py-4 text-right text-sm font-medium text-slate-800">
                                    @if ($totalMyr > 0)
                                        {{ $isApprox ? '≈ ' : '' }}MYR {{ number_format($totalMyr, 2) }}
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm font-medium text-slate-900">{{ $subscription->donor?->name ?? 'Unknown' }}</span>
                                    @if ($subscription->donor?->email)
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $subscription->donor->email }}</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->subscriptions->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="heroicon-o-arrow-path"
                title="No subscriptions found"
                description="Try adjusting your search or filter criteria."
            />
        @endif
    </x-ui.card>
</div>
