{{-- resources/views/livewire/app/donations/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Donations</h1>
            <p class="mt-1 text-sm text-slate-500">Track and manage all donations</p>
        </div>
        <x-ui.button wireClick="$set('showExportModal', true)" variant="outline">
            <x-heroicon-o-arrow-up-tray class="size-4" />
            Quick Export
        </x-ui.button>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Total Donations" value="{{ number_format($this->totalCount) }}" />
        <x-ui.stat-card
            label="Total Amount"
            value="{{ ($this->totalAmount['isApproximate'] ? '≈ ' : '').'MYR '.number_format($this->totalAmount['amount'], 2) }}"
            subtext="{{ $this->originalAmounts->isNotEmpty() ? $this->originalAmounts->map(fn ($amount, $currency) => $currency.' '.number_format($amount, 2))->join(', ') : null }}"
        />
    </div>

    {{-- Filter chips --}}
    <div class="flex flex-wrap items-center gap-2">

        {{-- Date chip --}}
        @php
            $dateActive = $period !== 'all_time';
            $dateLabel  = $this->dateChipLabel;
        @endphp
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
                        $wire.set('dateFrom', s);
                        $wire.set('dateTo', e);
                        $wire.set('period', 'custom');
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
                isToday(d) {
                    return d === new Date().toISOString().slice(0, 10);
                }
            }"
            @click.outside="open = false"
        >
            <button
                @click="open = !open"
                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors"
                :class="open ? 'border-slate-400 bg-slate-50 text-slate-800' : ''"
                @class([
                    'border-teal-600 bg-teal-50 font-medium text-teal-700' => $dateActive,
                    'border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800' => ! $dateActive,
                ])
            >
                <x-heroicon-o-calendar-days class="size-3.5 shrink-0" />
                <span>{{ $dateLabel }}</span>
                @if ($dateActive)
                    <span wire:click.stop="clearDate" class="ml-0.5 cursor-pointer rounded-full text-teal-500 hover:text-teal-800">
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
                {{-- Dual calendar --}}
                <div class="flex gap-8 p-5">
                    @foreach ([['leftYear', 'leftMonth', false], ['rightYear', 'rightMonth', true]] as [$yr, $mo, $isRight])
                    <div class="w-48">
                        <div class="mb-3 flex items-center justify-between">
                            @if (! $isRight)
                                <button @click="prevMonth()" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                    <x-heroicon-o-chevron-left class="size-4" />
                                </button>
                            @else
                                <div class="size-6"></div>
                            @endif
                            <span class="text-sm font-semibold text-slate-700" x-text="monthName({{ $mo }}) + ' ' + {{ $yr }}"></span>
                            @if ($isRight)
                                <button @click="nextMonth()" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                    <x-heroicon-o-chevron-right class="size-4" />
                                </button>
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
                                                'bg-teal-600 text-white font-semibold shadow-sm': isStart(fmt({{ $yr }}, {{ $mo }}, day)) || isEnd(fmt({{ $yr }}, {{ $mo }}, day)),
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

                {{-- Presets --}}
                <div class="w-36 border-l border-slate-100 py-3">
                    @foreach ([
                        ['all_time',    'All'],
                        ['today',       'Today'],
                        ['yesterday',   'Yesterday'],
                        ['7_days',      'Last 7 days'],
                        ['14_days',     'Last 14 days'],
                        ['30_days',     'Last 30 days'],
                        ['this_week',   'This week'],
                        ['this_month',  'This month'],
                        ['this_year',   'This year'],
                        ['last_week',   'Last week'],
                        ['last_month',  'Last month'],
                        ['last_year',   'Last year'],
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

        {{-- Campaign chip --}}
        <flux:dropdown>
            <button class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors @if($campaignFilter) border-teal-600 bg-teal-50 font-medium text-teal-700 @else border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800 @endif">
                Campaign
                @if ($campaignFilter)
                    <span wire:click.stop="$set('campaignFilter', '')" class="ml-0.5 cursor-pointer text-teal-500 hover:text-teal-800">
                        <x-heroicon-o-x-mark class="size-3.5" />
                    </span>
                @endif
            </button>
            <flux:menu class="max-h-72 w-56 overflow-y-auto">
                <flux:menu.item wire:click="$set('campaignFilter', '')" @class(['font-semibold text-teal-700' => ! $campaignFilter])>All Campaigns</flux:menu.item>
                <flux:menu.separator />
                @foreach ($this->campaigns as $campaign)
                    <flux:menu.item wire:click="$set('campaignFilter', '{{ $campaign->id }}')" @class(['font-semibold text-teal-700' => $campaignFilter == $campaign->id])>
                        {{ $campaign->title }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>

        {{-- Status chip --}}
        <flux:dropdown>
            <button class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors @if($statusFilter) border-teal-600 bg-teal-50 font-medium text-teal-700 @else border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800 @endif">
                @if ($statusFilter)
                    {{ ucfirst($statusFilter) }}
                @else
                    Status
                @endif
                @if ($statusFilter)
                    <span wire:click.stop="$set('statusFilter', '')" class="ml-0.5 cursor-pointer text-teal-500 hover:text-teal-800">
                        <x-heroicon-o-x-mark class="size-3.5" />
                    </span>
                @endif
            </button>
            <flux:menu>
                <flux:menu.item wire:click="$set('statusFilter', '')"    @class(['font-semibold text-teal-700' => ! $statusFilter])>All</flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item wire:click="$set('statusFilter', 'succeeded')" @class(['font-semibold text-teal-700' => $statusFilter === 'succeeded'])>Succeeded</flux:menu.item>
                <flux:menu.item wire:click="$set('statusFilter', 'pending')"   @class(['font-semibold text-teal-700' => $statusFilter === 'pending'])>Pending</flux:menu.item>
                <flux:menu.item wire:click="$set('statusFilter', 'failed')"    @class(['font-semibold text-teal-700' => $statusFilter === 'failed'])>Failed</flux:menu.item>
                <flux:menu.item wire:click="$set('statusFilter', 'refunded')"  @class(['font-semibold text-teal-700' => $statusFilter === 'refunded'])>Refunded</flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        {{-- Frequency chip --}}
        <flux:dropdown>
            <button class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors @if($frequencyFilter) border-teal-600 bg-teal-50 font-medium text-teal-700 @else border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800 @endif">
                @if ($frequencyFilter === 'one_time')
                    One-time
                @elseif ($frequencyFilter === 'recurring')
                    Recurring
                @else
                    Frequency
                @endif
                @if ($frequencyFilter)
                    <span wire:click.stop="$set('frequencyFilter', '')" class="ml-0.5 cursor-pointer text-teal-500 hover:text-teal-800">
                        <x-heroicon-o-x-mark class="size-3.5" />
                    </span>
                @endif
            </button>
            <flux:menu>
                <flux:menu.item wire:click="$set('frequencyFilter', '')"          @class(['font-semibold text-teal-700' => ! $frequencyFilter])>All</flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item wire:click="$set('frequencyFilter', 'one_time')"  @class(['font-semibold text-teal-700' => $frequencyFilter === 'one_time'])>One-time</flux:menu.item>
                <flux:menu.item wire:click="$set('frequencyFilter', 'recurring')" @class(['font-semibold text-teal-700' => $frequencyFilter === 'recurring'])>Recurring</flux:menu.item>
            </flux:menu>
        </flux:dropdown>

        {{-- More filters (search) --}}
        <flux:dropdown>
            <button class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors @if($search) border-teal-600 bg-teal-50 font-medium text-teal-700 @else border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-800 @endif">
                <x-heroicon-o-plus class="size-3.5" />
                More filters
                @if ($search)
                    <span wire:click.stop="$set('search', '')" class="ml-0.5 cursor-pointer text-teal-500 hover:text-teal-800">
                        <x-heroicon-o-x-mark class="size-3.5" />
                    </span>
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

    {{-- Donations Table --}}
    <x-ui.card>
        @if ($this->donations->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="whitespace-nowrap min-w-[180px] px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">
                                <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1">
                                    Date
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
                            <th scope="col" class="whitespace-nowrap min-w-[180px] px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">
                                <button wire:click="sortBy('gross_amount')" class="group inline-flex items-center gap-1">
                                    Donation
                                    @if ($sortField === 'gross_amount')
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">
                                <button wire:click="sortBy('donor_name')" class="group inline-flex items-center gap-1">
                                    Supporter
                                    @if ($sortField === 'donor_name')
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">
                                <button wire:click="sortBy('status')" class="group inline-flex items-center gap-1">
                                    Status
                                    @if ($sortField === 'status')
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">
                                <button wire:click="sortBy('campaign')" class="group inline-flex items-center gap-1">
                                    Campaign
                                    @if ($sortField === 'campaign')
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
                        @foreach ($this->donations as $donation)
                            <tr
                                class="cursor-pointer transition-colors hover:bg-slate-50"
                                onclick="window.location='{{ route('app.donations.show', $donation) }}'"
                            >
                                <td class="whitespace-nowrap min-w-[180px] px-5 py-4 text-sm text-slate-500">
                                    {{ myrTime($donation->created_at) }}
                                </td>
                                <td class="whitespace-nowrap min-w-[180px] px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <x-donation-report-amount :donation="$donation" />
                                        {{-- Payment method icon --}}
                                        @php
                                            $pmType = $donation->payment_method_type;
                                            $pmBrand = strtolower($donation->payment_method_brand ?? '');
                                        @endphp
                                        @if ($pmBrand === 'apple_pay' || $pmType === 'apple_pay')
                                            <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-600">Pay</span>
                                        @elseif ($pmBrand === 'google_pay' || $pmType === 'google_pay')
                                            <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-600">G Pay</span>
                                        @elseif ($pmType === 'card' || filled($pmBrand))
                                            <x-ui.tooltip :text="\Illuminate\Support\Str::headline($pmBrand)">
                                                <x-heroicon-o-credit-card class="size-4 text-slate-400" />
                                            </x-ui.tooltip>
                                        @endif
                                        {{-- Recurring indicator --}}
                                        @if ($donation->subscription_id)
                                            <x-ui.tooltip text="Recurring">
                                                <x-heroicon-o-arrow-path class="size-4 text-teal-500" />
                                            </x-ui.tooltip>
                                            <x-ui.tooltip :text="Number::ordinal($donation->subscription?->payment_count ?? 1).' installment'">
                                                <span class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-teal-50 px-1 text-[10px] font-semibold text-teal-600">{{ $donation->subscription?->payment_count ?? 1 }}</span>
                                            </x-ui.tooltip>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-medium text-slate-900">{{ $donation->donor?->name ?? 'Anonymous' }}</span>
                                        @if ($donation->device_type === 'mobile')
                                            <x-ui.tooltip text="Mobile donation">
                                                <x-heroicon-o-device-phone-mobile class="size-3.5 shrink-0 text-slate-400" />
                                            </x-ui.tooltip>
                                        @elseif ($donation->device_type === 'tablet')
                                            <x-ui.tooltip text="Tablet donation">
                                                <x-heroicon-o-device-tablet class="size-3.5 shrink-0 text-slate-400" />
                                            </x-ui.tooltip>
                                        @elseif ($donation->device_type === 'desktop')
                                            <x-ui.tooltip text="Desktop donation">
                                                <x-heroicon-o-computer-desktop class="size-3.5 shrink-0 text-slate-400" />
                                            </x-ui.tooltip>
                                        @endif
                                    </div>
                                    @if ($donation->donor?->email)
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $donation->donor->email }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <x-ui.badge status="{{ $donation->status->value }}" size="sm">
                                        {{ ucfirst($donation->status->value === 'succeeded' ? 'Succeeded' : $donation->status->value) }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    @if ($donation->campaign)
                                        <a
                                            href="{{ route('app.campaigns.edit', $donation->campaign) }}"
                                            wire:navigate.stop
                                            class="hover:text-teal-600"
                                            onclick="event.stopPropagation()"
                                        >
                                            {{ $donation->campaign->title }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($this->donations->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $this->donations->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state
                icon="heroicon-o-banknotes"
                title="No donations found"
                description="Try adjusting your filters or search criteria."
            />
        @endif
    </x-ui.card>

    {{-- Quick Export Modal --}}
    <flux:modal wire:model="showExportModal" name="quick-export-donations" class="max-h-[85vh] max-w-md">
        <form
            method="GET"
            action="{{ route('app.donations.export') }}"
            @submit="loading = true; setTimeout(() => loading = false, 3000)"
            x-data="{
                loading: false,
                fields: @js(collect($this->exportFields)->map(fn (array $field): array => ['key' => $field['key'], 'label' => $field['label'], 'selected' => $field['default'] ?? false])->values()),
                dragIndex: null,
                dragOverIndex: null,
                get selectAll() { return this.fields.length > 0 && this.fields.every(f => f.selected) },
                set selectAll(value) { this.fields.forEach(f => f.selected = value) },
                dragStart(index, event) {
                    this.dragIndex = index;
                    event.dataTransfer.effectAllowed = 'move';
                    let row = event.target.closest('[data-row]');
                    let handle = event.target.closest('[data-handle]');
                    if (row && handle && event.dataTransfer.setDragImage) {
                        let rowRect = row.getBoundingClientRect();
                        let handleRect = handle.getBoundingClientRect();
                        event.dataTransfer.setDragImage(
                            row,
                            handleRect.left - rowRect.left + handleRect.width / 2,
                            handleRect.top - rowRect.top + handleRect.height / 2
                        );
                    }
                },
                dragEnter(index, event) { this.dragOverIndex = index; event.dataTransfer.dropEffect = 'move' },
                dragEnd() { this.dragIndex = null; this.dragOverIndex = null },
                drop(index, event) {
                    event.dataTransfer.dropEffect = 'move'
                    if (this.dragIndex === null || this.dragIndex === index) return
                    let item = this.fields.splice(this.dragIndex, 1)[0]
                    this.fields.splice(index, 0, item)
                    this.dragIndex = null
                    this.dragOverIndex = null
                }
            }"
            class="flex max-h-[85vh] flex-col gap-6"
        >
            <div class="shrink-0">
                <h3 class="text-lg font-semibold text-slate-900">Export donations</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Pick the fields you'd like to include in your export, then drag and drop to set the column order.
                </p>
            </div>

            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="statusFilter" value="{{ $statusFilter }}">
            <input type="hidden" name="campaignFilter" value="{{ $campaignFilter }}">
            <input type="hidden" name="frequencyFilter" value="{{ $frequencyFilter }}">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="dateFrom" value="{{ $dateFrom }}">
            <input type="hidden" name="dateTo" value="{{ $dateTo }}">

            <div class="shrink-0">
                <label class="block text-sm font-semibold text-slate-900">Download exported file as</label>
                <div class="mt-3 flex items-center gap-6">
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <span class="relative flex size-4 shrink-0 items-center justify-center">
                            <input type="radio" name="format" value="csv" checked class="peer sr-only absolute inset-0">
                            <span class="absolute inset-0 rounded-full border border-slate-300 bg-white peer-checked:border-teal-600 peer-checked:bg-teal-600"></span>
                            <span class="relative size-1.5 rounded-full bg-white opacity-0 transition-opacity peer-checked:opacity-100"></span>
                        </span>
                        <span class="text-sm text-slate-700">CSV</span>
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <span class="relative flex size-4 shrink-0 items-center justify-center">
                            <input type="radio" name="format" value="xls" class="peer sr-only absolute inset-0">
                            <span class="absolute inset-0 rounded-full border border-slate-300 bg-white peer-checked:border-teal-600 peer-checked:bg-teal-600"></span>
                            <span class="relative size-1.5 rounded-full bg-white opacity-0 transition-opacity peer-checked:opacity-100"></span>
                        </span>
                        <span class="text-sm text-slate-700">XLS</span>
                    </label>
                </div>
            </div>

            <div class="flex min-h-0 flex-col">
                <label class="block shrink-0 text-sm font-semibold text-slate-900">Exported fields</label>
                <div class="mt-3 flex-1 space-y-2 overflow-y-auto pr-1">
                    {{-- Select all --}}
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-slate-50 px-3 py-2.5">
                        <span class="relative flex size-4 shrink-0 items-center justify-center">
                            <input type="checkbox" x-model="selectAll" class="peer sr-only absolute inset-0">
                            <span class="absolute inset-0 rounded border border-slate-300 bg-white peer-checked:border-teal-600 peer-checked:bg-teal-600"></span>
                            <svg class="relative size-3 text-white opacity-0 transition-opacity peer-checked:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </span>
                        <span class="text-sm text-slate-700">Select all</span>
                    </label>

                    <template x-for="(field, index) in fields" :key="field.key">
                        <div
                            data-row
                            @dragenter.prevent="dragEnter(index, $event)"
                            @dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                            @drop.prevent="drop(index, $event)"
                            class="flex items-center gap-3 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 transition-colors"
                            :class="dragOverIndex === index ? 'bg-teal-50 ring-1 ring-teal-300' : ''"
                        >
                            <div
                                draggable="true"
                                data-handle
                                @dragstart="dragStart(index, $event)"
                                @dragend="dragEnd()"
                                class="cursor-grab p-1 -m-1 text-slate-400"
                            >
                                <svg class="size-4" viewBox="0 0 16 16" fill="currentColor">
                                    <circle cx="4" cy="4" r="1.5" />
                                    <circle cx="8" cy="4" r="1.5" />
                                    <circle cx="12" cy="4" r="1.5" />
                                    <circle cx="4" cy="12" r="1.5" />
                                    <circle cx="8" cy="12" r="1.5" />
                                    <circle cx="12" cy="12" r="1.5" />
                                </svg>
                            </div>
                            <label class="flex flex-1 cursor-pointer items-center gap-3">
                                <span class="relative flex size-4 shrink-0 items-center justify-center">
                                    <input type="checkbox" name="fields[]" :value="field.key" x-model="field.selected" class="peer sr-only absolute inset-0">
                                    <span class="absolute inset-0 rounded border border-slate-300 bg-white peer-checked:border-teal-600 peer-checked:bg-teal-600"></span>
                                    <svg class="relative size-3 text-white opacity-0 transition-opacity peer-checked:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                                <span class="text-sm text-slate-700" x-text="field.label"></span>
                            </label>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex shrink-0 justify-end gap-3 pt-2">
                <flux:modal.close>
                    <x-ui.button type="button" variant="secondary">Cancel</x-ui.button>
                </flux:modal.close>
                <x-ui.button type="submit" variant="primary" x-bind:disabled="loading">
                    <x-heroicon-o-arrow-path class="size-4 animate-spin" x-show="loading" />
                    <x-heroicon-o-arrow-up-tray class="size-4" x-show="! loading" />
                    <span x-text="loading ? 'Exporting...' : 'Export donations'"></span>
                </x-ui.button>
            </div>
        </form>
    </flux:modal>
</div>
