{{-- resources/views/components/ui/line-chart.blade.php --}}
@props([
    'data' => [],          // array of ['date' => 'j M', 'amount' => float, 'has_approximation' => bool]
    'color' => '#3b82f6',
    'height' => 220,       // px
    'prefix' => 'MYR',
    'label' => 'Total',    // tooltip title
])

@php
    $series = collect($data)->map(fn ($p) => [
        'date' => $p['date'],
        'amount' => (float) $p['amount'],
        'approx' => (bool) ($p['has_approximation'] ?? false),
    ])->values();

    $amounts = $series->pluck('amount')->all();
    $max = count($amounts) ? max($amounts) : 0;
    $axisMax = $max > 0 ? $max : 1;

    // Four horizontal gridlines dividing the axis into thirds (0 → axisMax).
    $ticks = collect([1, 2 / 3, 1 / 3, 0])->map(fn ($frac) => [
        'value' => $axisMax * $frac,
        'y' => round((1 - $frac) * 100, 3),
    ])->all();

    // Coordinate space 0..100 on both axes; the SVG stretches horizontally with a non-scaling stroke.
    $points = $series->map(function ($p, $i) use ($series, $axisMax) {
        $count = max(1, $series->count() - 1);
        $x = $count > 0 ? ($i / $count) * 100 : 0;
        $y = 100 - ($p['amount'] / $axisMax) * 100;

        return [
            'x' => round($x, 3),
            'y' => round($y, 3),
            'date' => $p['date'],
            'amount' => $p['amount'],
            'approx' => $p['approx'],
        ];
    })->all();

    $linePath = collect($points)
        ->map(fn ($pt, $i) => ($i === 0 ? 'M' : 'L').' '.$pt['x'].' '.$pt['y'])
        ->implode(' ');

    $areaPath = count($points)
        ? 'M '.$points[0]['x'].' 100 '
            .collect($points)->map(fn ($pt) => 'L '.$pt['x'].' '.$pt['y'])->implode(' ')
            .' L '.end($points)['x'].' 100 Z'
        : '';

    $gradientId = 'lineChartFill-'.uniqid();
    $first = $series->first();
    $last = $series->last();
@endphp

<style>
    .line-chart {
        opacity: 0;
        transform: translateY(12px);
        transition: opacity 600ms ease-out, transform 600ms ease-out;
    }
    .line-chart.chart-visible {
        opacity: 1;
        transform: translateY(0);
    }
    .line-chart .line-path {
        stroke-dasharray: 1000;
        stroke-dashoffset: 1000;
        transition: stroke-dashoffset 1200ms ease-out;
    }
    .line-chart.chart-visible .line-path {
        stroke-dashoffset: 0;
    }
    .line-chart .area-path {
        opacity: 0;
        transition: opacity 800ms ease-out 200ms;
    }
    .line-chart.chart-visible .area-path {
        opacity: 1;
    }
</style>

<div
    {{ $attributes->merge(['class' => 'line-chart w-full']) }}
    x-data="{
        points: @js($points),
        active: null,
        prefix: @js($prefix),
        visible: false,
        onMove(event) {
            const rect = this.$refs.plot.getBoundingClientRect();
            if (rect.width === 0 || this.points.length === 0) return;
            const ratio = Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width));
            let nearest = 0;
            let best = Infinity;
            this.points.forEach((pt, i) => {
                const dist = Math.abs(pt.x - ratio * 100);
                if (dist < best) { best = dist; nearest = i; }
            });
            this.active = nearest;
        },
        money(value) {
            return this.prefix + ' ' + Number(value).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        observe() {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    this.visible = true;
                    observer.disconnect();
                }
            }, { threshold: 0.2, rootMargin: '0px 0px -40px 0px' });
            observer.observe(this.$el);
        },
    }"
    x-init="observe()"
    :class="{ 'chart-visible': visible }"
>
    <div class="relative pr-14" style="height: {{ $height }}px">
        {{-- Plot area (excludes the y-axis label gutter) --}}
        <div
            class="absolute inset-y-0 left-0 right-14"
            x-ref="plot"
            @mousemove="onMove($event)"
            @mouseleave="active = null"
            @touchmove.passive="onMove($event.touches[0])"
            @touchend="active = null"
        >
            <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                    <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.16" />
                        <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0" />
                    </linearGradient>
                </defs>

                {{-- Gridlines --}}
                @foreach($ticks as $tick)
                    <line x1="0" x2="100" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="#eef2f7" stroke-width="1" vector-effect="non-scaling-stroke" />
                @endforeach

                @if($areaPath)
                    <path d="{{ $areaPath }}" fill="url(#{{ $gradientId }})" class="area-path" />
                @endif
                <path d="{{ $linePath }}" fill="none" stroke="{{ $color }}" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" class="line-path" />

                {{-- Hover guide line --}}
                <template x-if="active !== null">
                    <line :x1="points[active].x" :x2="points[active].x" y1="0" y2="100" stroke="#94a3b8" stroke-width="1" vector-effect="non-scaling-stroke" opacity="0.6" />
                </template>
            </svg>

            {{-- Hover dot --}}
            <template x-if="active !== null">
                <div
                    class="pointer-events-none absolute size-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow"
                    style="background: {{ $color }}"
                    :style="`left: ${points[active].x}%; top: ${points[active].y}%`"
                ></div>
            </template>

            {{-- Hover tooltip (white card) --}}
            <template x-if="active !== null">
                <div
                    class="pointer-events-none absolute z-10 min-w-[200px] -translate-x-1/2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 shadow-lg"
                    :style="`left: ${Math.min(82, Math.max(18, points[active].x))}%; top: 4px`"
                >
                    <div class="text-sm font-medium text-slate-500">{{ $label }}</div>
                    <div class="mt-1 flex items-center justify-between gap-6">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full" style="background: {{ $color }}"></span>
                            <span class="text-sm text-slate-600" x-text="points[active].date"></span>
                        </div>
                        <span class="text-sm font-bold text-slate-900" x-text="(points[active].approx ? '≈ ' : '') + money(points[active].amount)"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- Y-axis labels --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 w-14">
            @foreach($ticks as $tick)
                <span class="absolute right-0 -translate-y-1/2 text-xs text-slate-400" style="top: {{ $tick['y'] }}%">
                    {{ $prefix }} {{ number_format($tick['value']) }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- X-axis labels (first + last) --}}
    @if($series->count() > 1)
        <div class="mt-2 flex justify-between pr-14 text-xs text-slate-400">
            <span>{{ $first['date'] }}</span>
            <span>{{ $last['date'] }}</span>
        </div>
    @endif
</div>
