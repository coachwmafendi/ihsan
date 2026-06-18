{{-- resources/views/livewire/app/supporters/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Supporters</h1>
            <p class="mt-1 text-sm text-slate-500">Manage and view all supporters</p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Total Supporters" value="{{ number_format($this->totalCount) }}" />
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or email..."
                class="h-10 w-64 rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
        </div>

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

        {{-- Per Page --}}
        <div class="ml-auto flex items-center gap-2">
            <x-heroicon-o-list-bullet class="size-4 text-slate-400" />
            <select
                wire:model.live="perPage"
                class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            >
                <option value="10">10 rows</option>
                <option value="25">25 rows</option>
                <option value="50">50 rows</option>
                <option value="100">100 rows</option>
            </select>
        </div>
    </div>

    {{-- Donors Table --}}
    <x-ui.card>
        @if ($this->donors->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('name')" class="group inline-flex items-center gap-1">
                                    Name
                                    @if ($sortField === 'name')
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
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('email')" class="group inline-flex items-center gap-1">
                                    Email
                                    @if ($sortField === 'email')
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
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('donations_count')" class="group inline-flex items-center gap-1">
                                    Donations
                                    @if ($sortField === 'donations_count')
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
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('lifetime_report_amount')" class="group inline-flex items-center gap-1">
                                    Lifetime donated
                                    @if ($sortField === 'lifetime_report_amount')
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
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('donations_min_created_at')" class="group inline-flex items-center gap-1">
                                    First Donation
                                    @if ($sortField === 'donations_min_created_at')
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
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('donations_max_created_at')" class="group inline-flex items-center gap-1">
                                    Last Donation
                                    @if ($sortField === 'donations_max_created_at')
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
                            <th scope="col" class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1">
                                    Created
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->donors as $donor)
                            <tr
                                class="cursor-pointer transition-colors hover:bg-slate-50"
                                onclick="window.location='{{ route('app.supporters.show', $donor) }}'"
                            >
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ \Illuminate\Support\Str::title($donor->name) }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ $donor->email }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-900">
                                    {{ number_format($donor->donations_count) }}
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $exact = $exactAmounts->get($donor->id, collect());
                                        $exactLabel = $exact->isNotEmpty()
                                            ? $exact->map(fn ($amount, $currency) => strtoupper($currency).' '.number_format((float) $amount, 2))->join(', ')
                                            : null;
                                        $tooltip = $exactLabel;
                                    @endphp
                                    @php
                                        $showApprox = $donor->has_report_approximation;
                                        $prefix = $showApprox ? '≈ ' : '';
                                    @endphp
                                    @if ($tooltip)
                                        <x-ui.tooltip :text="$tooltip">
                                    @endif
                                        <span class="text-sm font-semibold text-slate-900">{{ $prefix }}MYR {{ number_format((float) $donor->lifetime_report_amount, 2) }}</span>
                                    @if ($tooltip)
                                        </x-ui.tooltip>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                    {{ $donor->donations_min_created_at ? \Carbon\Carbon::parse($donor->donations_min_created_at)->format('M d, Y') : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                    {{ $donor->donations_max_created_at ? \Carbon\Carbon::parse($donor->donations_max_created_at)->format('M d, Y') : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                    {{ $donor->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->donors->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="heroicon-o-users"
                title="No supporters found"
                description="Try adjusting your search criteria."
            />
        @endif
    </x-ui.card>
</div>
