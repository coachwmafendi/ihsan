{{-- resources/views/components/ui/line-chart.blade.php --}}
@props([
    'data' => [],          // array of ['date' => 'j M', 'amount' => float, 'has_approximation' => bool]
    'color' => '#3b82f6',
    'height' => 160,       // px
    'prefix' => 'MYR',
])

@php
    $series = collect($data)->map(fn ($p) => [
        'date' => $p['date'],
        'amount' => (float) $p['amount'],
        'approx' => (bool) ($p['has_approximation'] ?? false),
    ])->values();

    $amounts = $series->pluck('amount')->all();
    $max = count($amounts) ? max($amounts) : 0;
    $min = 0; // baseline at zero so spikes read against an empty period
    $range = ($max - $min) > 0 ? ($max - $min) : 1;

    // Coordinate space 0..100 on both axes; SVG stretches horizontally with a non-scaling stroke.
    $points = $series->map(function ($p, $i) use ($series, $range, $min) {
        $count = max(1, $series->count() - 1);
        $x = $count > 0 ? ($i / $count) * 100 : 0;
        $y = 100 - (($p['amount'] - $min) / $range) * 100;

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
@endphp

<div
    {{ $attributes->merge(['class' => 'w-full']) }}
    x-data="{
        points: @js($points),
        active: null,
        prefix: @js($prefix),
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
    }"
>
    <div
        class="relative"
        style="height: {{ $height }}px"
        x-ref="plot"
        @mousemove="onMove($event)"
        @mouseleave="active = null"
        @touchmove.passive="onMove($event.touches[0])"
        @touchend="active = null"
    >
        <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <defs>
                <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.18" />
                    <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0" />
                </linearGradient>
            </defs>
            @if($areaPath)
                <path d="{{ $areaPath }}" fill="url(#{{ $gradientId }})" />
            @endif
            <path d="{{ $linePath }}" fill="none" stroke="{{ $color }}" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />

            {{-- Hover guide line --}}
            <template x-if="active !== null">
                <line :x1="points[active].x" :x2="points[active].x" y1="0" y2="100" stroke="{{ $color }}" stroke-width="1" stroke-dasharray="3 3" vector-effect="non-scaling-stroke" opacity="0.5" />
            </template>
        </svg>

        {{-- Hover dot --}}
        <template x-if="active !== null">
            <div
                class="pointer-events-none absolute size-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow"
                style="background: {{ $color }}"
                :style="`left: ${points[active].x}%; top: ${points[active].y}%`"
            ></div>
        </template>

        {{-- Hover tooltip --}}
        <template x-if="active !== null">
            <div
                class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs text-white shadow-lg"
                :style="`left: ${Math.min(92, Math.max(8, points[active].x))}%; top: ${Math.max(12, points[active].y)}%; margin-top: -10px`"
            >
                <div class="font-semibold" x-text="(points[active].approx ? '≈ ' : '') + money(points[active].amount)"></div>
                <div class="text-slate-300" x-text="points[active].date"></div>
            </div>
        </template>
    </div>

    {{-- X-axis labels --}}
    @if($series->count() > 1)
        <div class="mt-3 flex justify-between text-[11px] text-slate-400">
            @php
                $labelStep = (int) ceil($series->count() / 6);
            @endphp
            @foreach($series as $i => $p)
                @if($i % $labelStep === 0 || $i === $series->count() - 1)
                    <span>{{ $p['date'] }}</span>
                @endif
            @endforeach
        </div>
    @endif
</div>
