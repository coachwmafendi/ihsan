{{-- resources/views/components/ui/date-range-calendar.blade.php --}}
@props([
    'wireFrom' => 'dateFrom',
    'wireTo' => 'dateTo',
    'labelFrom' => 'Start',
    'labelTo' => 'End',
    'initialFrom' => null,
    'initialTo' => null,
    'inline' => false,
    'applyAction' => null,
    'today' => null,
    'align' => 'left',
])

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        open: @js($inline),
        leftYear: new Date().getFullYear(),
        leftMonth: new Date().getMonth() - 1,
        startDate: @js($initialFrom ?: null),
        endDate: @js($initialTo ?: null),
        hovering: null,
        {{-- Today as the figures are dated, not as the visitor's device has it.
             new Date() reads the browser clock and toISOString() then reads UTC,
             which rings the wrong day for anyone reading before 8am in KL. --}}
        today: @js($today ?: now()->format('Y-m-d')),
        init() {
            {{-- Parsed by parts: new Date('2026-01-01') is UTC midnight, which
                 is the previous day anywhere west of Greenwich. --}}
            if (this.startDate) {
                {{-- Open on the month the range starts in, so the range itself
                     is what the two months show. Opening one month earlier put
                     a 16 Aug - 14 Sep range on a July and August pair, where
                     the end of it could not be seen at all. --}}
                let [y, m] = this.startDate.split('-').map(Number);
                this.leftYear = y;
                this.leftMonth = m - 1;

                return;
            }

            {{-- Nothing chosen yet: this month on the right, since a range
                 being picked is far more often behind us than ahead. --}}
            let [y, m] = this.today.split('-').map(Number);
            this.leftYear = m === 1 ? y - 1 : y;
            this.leftMonth = m === 1 ? 11 : m - 2;
        },
        get rightYear() { return this.leftMonth === 11 ? this.leftYear + 1 : this.leftYear },
        get rightMonth() { return (this.leftMonth + 1) % 12 },
        prevMonth() { if (this.leftMonth === 0) { this.leftMonth = 11; this.leftYear-- } else this.leftMonth-- },
        nextMonth() { if (this.leftMonth === 11) { this.leftMonth = 0; this.leftYear++ } else this.leftMonth++ },
        daysInMonth(y, m) { return new Date(y, m + 1, 0).getDate() },
        firstWeekday(y, m) { let d = new Date(y, m, 1).getDay(); return d === 0 ? 6 : d - 1 },
        getMonthDays(y, m) {
            let days = Array(this.firstWeekday(y, m)).fill(null);
            for (let i = 1; i <= this.daysInMonth(y, m); i++) days.push(i);
            return days;
        },
        fmt(y, m, d) { return String(y) + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0') },
        displayDate(dateStr) {
            if (! dateStr) return '—';
            let [y, m, d] = dateStr.split('-');
            return `${d} / ${m} / ${y}`;
        },
        monthName(m) { return ['January','February','March','April','May','June','July','August','September','October','November','December'][m] },
        selectDay(dateStr) {
            if (! this.startDate || (this.startDate && this.endDate)) {
                this.startDate = dateStr;
                this.endDate = null;
            } else {
                let [s, e] = dateStr < this.startDate ? [dateStr, this.startDate] : [this.startDate, dateStr];
                this.startDate = s;
                this.endDate = e;

                {{-- Where there is an Apply, the range is not read until it is
                     pressed: writing on the second click is what made every
                     half-chosen range recompute the page behind it. --}}
                if (this.holdsUntilApplied) return;

                $wire.set('{{ $wireFrom }}', s);
                $wire.set('{{ $wireTo }}', e);
                this.open = @js($inline) || false;
            }
        },
        isStart(d) { return d === this.startDate },
        isEnd(d) { return d === this.endDate },
        isInRange(d) {
            if (! this.startDate) return false;
            let end = this.endDate || this.hovering;
            if (! end) return false;
            let [s, e] = this.startDate <= end ? [this.startDate, end] : [end, this.startDate];
            return d > s && d < e;
        },
        isToday(d) {
            return d === this.today;
        },
        holdsUntilApplied: @js($applyAction !== null),
        get isComplete() { return Boolean(this.startDate && this.endDate) },
        clear() {
            this.startDate = null;
            this.endDate = null;
            this.hovering = null;
        },
        apply() {
            if (! this.isComplete) return;

            $wire.set('{{ $wireFrom }}', this.startDate);
            $wire.set('{{ $wireTo }}', this.endDate);
            $wire.call(@js($applyAction));
            this.open = @js($inline) || false;
        }
    }"
    x-init="init()"
    @if (! $inline)
        @click.outside="open = false"
    @endif
>
    @if (! $inline)
        <div class="flex items-center gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600">{{ $labelFrom }}</label>
                <button
                    type="button"
                    @click="open = !open"
                    class="mt-1 flex w-36 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm hover:bg-slate-50 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    x-text="displayDate(startDate)"
                ></button>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600">{{ $labelTo }}</label>
                <button
                    type="button"
                    @click="open = !open"
                    class="mt-1 flex w-36 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm hover:bg-slate-50 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    x-text="displayDate(endDate)"
                ></button>
            </div>
        </div>
    @endif

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        @class([
            'rounded-xl border border-slate-200 bg-white p-5 shadow-xl',
            'absolute top-full z-50 mt-2' => ! $inline,
            'left-0' => ! $inline && $align !== 'right',
            'right-0' => ! $inline && $align === 'right',
        ])
        @if (! $inline)
            style="display:none"
        @endif
    >
        <div class="flex flex-col gap-6 sm:flex-row sm:gap-8">
            @foreach ([['leftYear', 'leftMonth', false], ['rightYear', 'rightMonth', true]] as [$yr, $mo, $isRight])
                <div class="w-48">
                    <div class="mb-3 flex items-center justify-between">
                        @if (! $isRight)
                            <button type="button" @click="prevMonth()" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <x-heroicon-o-chevron-left class="size-4" />
                            </button>
                        @else
                            <div class="size-6"></div>
                        @endif
                        <span class="text-sm font-semibold text-slate-700" x-text="monthName({{ $mo }}) + ' ' + {{ $yr }}"></span>
                        @if ($isRight)
                            <button type="button" @click="nextMonth()" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
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
                                        type="button"
                                        @click="selectDay(fmt({{ $yr }}, {{ $mo }}, day))"
                                        @mouseover="if (startDate && ! endDate) hovering = fmt({{ $yr }}, {{ $mo }}, day)"
                                        @mouseleave="hovering = null"
                                        class="size-7 rounded-full text-xs transition-colors"
                                        :class="{
                                            'bg-teal-600 text-white font-semibold shadow-sm': isStart(fmt({{ $yr }}, {{ $mo }}, day)) || isEnd(fmt({{ $yr }}, {{ $mo }}, day)),
                                            'bg-teal-100 text-teal-800': isInRange(fmt({{ $yr }}, {{ $mo }}, day)),
                                            'ring-1 ring-teal-400 text-teal-700': isToday(fmt({{ $yr }}, {{ $mo }}, day)) && ! isStart(fmt({{ $yr }}, {{ $mo }}, day)) && ! isEnd(fmt({{ $yr }}, {{ $mo }}, day)),
                                            'text-slate-700 hover:bg-slate-100': ! isStart(fmt({{ $yr }}, {{ $mo }}, day)) && ! isEnd(fmt({{ $yr }}, {{ $mo }}, day)) && ! isInRange(fmt({{ $yr }}, {{ $mo }}, day))
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

        @if ($applyAction !== null)
            <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                <button
                    type="button"
                    @click="clear()"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Clear
                </button>
                <button
                    type="button"
                    @click="apply()"
                    :disabled="! isComplete"
                    class="rounded-lg bg-teal-700 px-4 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Apply
                </button>
            </div>
        @endif
    </div>
</div>
