{{-- resources/views/components/ui/date-picker.blade.php --}}
@props([
    'wireModel',
    'initial' => null,
    'inline' => false,
    'label' => null,
])

<div
    @class(['relative', 'w-full' => ! $inline])
    x-data="{
        open: @js($inline),
        year: new Date().getFullYear(),
        month: new Date().getMonth(),
        selected: @js($initial ?: null),
        today: new Date().toISOString().slice(0, 10),
        init() {
            let base = this.selected ? new Date(this.selected) : new Date();
            this.year = base.getFullYear();
            this.month = base.getMonth();
        },
        daysInMonth(y, m) { return new Date(y, m + 1, 0).getDate() },
        firstWeekday(y, m) { let d = new Date(y, m, 1).getDay(); return d === 0 ? 6 : d - 1 },
        getMonthDays(y, m) {
            let days = Array(this.firstWeekday(y, m)).fill(null);
            for (let i = 1; i <= this.daysInMonth(y, m); i++) days.push(i);
            return days;
        },
        fmt(y, m, d) { return String(y) + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0') },
        display(dateStr) {
            if (! dateStr) return '';
            let [y, m, d] = dateStr.split('-');
            return `${d} / ${m} / ${y}`;
        },
        monthName(m) { return ['January','February','March','April','May','June','July','August','September','October','November','December'][m] },
        select(y, m, d) {
            this.selected = this.fmt(y, m, d);
            $wire.set('{{ $wireModel }}', this.selected);
            this.open = @js($inline) || false;
        },
        isSelected(y, m, d) { return this.fmt(y, m, d) === this.selected },
        isToday(y, m, d) { return this.fmt(y, m, d) === this.today },
        prev() { if (this.month === 0) { this.month = 11; this.year-- } else this.month-- },
        next() { if (this.month === 11) { this.month = 0; this.year++ } else this.month++ },
    }"
    x-init="init()"
    @if (! $inline)
        @click.outside="open = false"
    @endif
>
    @if (! $inline)
        <button
            type="button"
            @click="open = !open"
            class="flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm hover:bg-slate-50"
            x-text="display(selected) || 'Select date'"
        ></button>
    @endif

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        @class([
            'w-64 rounded-xl border border-slate-200 bg-white p-4 shadow-xl',
            'absolute left-0 top-full z-50 mt-2' => ! $inline,
        ])
        @if (! $inline)
            style="display:none"
        @endif
    >
        @if ($label)
            <label class="block text-xs font-medium text-slate-600">{{ $label }}</label>
        @endif
        <div class="mt-1 flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm">
            <span
                :class="selected ? 'text-slate-900' : 'text-slate-400'"
                x-text="display(selected) || 'DD / MM / YYYY'"
            ></span>
        </div>

        <div class="mt-4">
            <div class="mb-3 flex items-center justify-between">
                <button type="button" @click="prev()" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <x-heroicon-o-chevron-left class="size-4" />
                </button>
                <span class="text-sm font-semibold text-slate-700" x-text="monthName(month) + ' ' + year"></span>
                <button type="button" @click="next()" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <x-heroicon-o-chevron-right class="size-4" />
                </button>
            </div>
            <div class="grid grid-cols-7 text-center">
                @foreach (['Mo','Tu','We','Th','Fr','Sa','Su'] as $wd)
                    <div class="py-1 text-[10px] font-medium uppercase text-slate-400">{{ $wd }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                <template x-for="(day, i) in getMonthDays(year, month)" :key="i">
                    <div class="flex items-center justify-center py-0.5">
                        <template x-if="day === null"><span></span></template>
                        <template x-if="day !== null">
                            <button
                                type="button"
                                @click="select(year, month, day)"
                                class="size-7 rounded-full text-xs transition-colors"
                                :class="{
                                    'bg-teal-600 text-white font-semibold shadow-sm': isSelected(year, month, day),
                                    'ring-1 ring-teal-400 text-teal-700': isToday(year, month, day) && ! isSelected(year, month, day),
                                    'text-slate-700 hover:bg-slate-100': ! isSelected(year, month, day)
                                }"
                                x-text="day"
                            ></button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
