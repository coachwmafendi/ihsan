{{-- resources/views/components/ui/date-range-calendar.blade.php --}}
@props([
    'wireFrom' => 'dateFrom',
    'wireTo' => 'dateTo',
    'labelFrom' => 'Start',
    'labelTo' => 'End',
    'initialFrom' => null,
    'initialTo' => null,
])

<div
    class="relative"
    x-data="{
        open: false,
        leftYear: new Date().getFullYear(),
        leftMonth: new Date().getMonth() - 1,
        startDate: @js($initialFrom ?: null),
        endDate: @js($initialTo ?: null),
        hovering: null,
        init() {
            let base = this.startDate ? new Date(this.startDate) : new Date();
            this.leftYear = base.getFullYear();
            this.leftMonth = base.getMonth() === 0 ? 11 : base.getMonth() - 1;
            if (base.getMonth() === 0) {
                this.leftYear--;
            }
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
                $wire.set('{{ $wireFrom }}', s);
                $wire.set('{{ $wireTo }}', e);
                this.open = false;
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
            return d === new Date().toISOString().slice(0, 10);
        }
    }"
    x-init="init()"
    @click.outside="open = false"
>
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

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="absolute left-0 top-full z-50 mt-2 rounded-xl border border-slate-200 bg-white p-5 shadow-xl"
        style="display:none"
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
    </div>
</div>
