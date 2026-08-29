@props([
    'data' => [],
    'size' => 220,
    'donut' => true,
    'centerLabel' => null,
    'footer' => null,
])

@php
$total = (float) collect($data)->sum('value');
// Hues ordered so neighbours stay apart for colourblind readers; blue-500 next
// to blue-600 read as one colour in a two-slice chart.
$colors = ['#2563eb', '#f59e0b', '#14b8a6', '#f43f5e', '#a855f7', '#10b981', '#f97316', '#06b6d4', '#6366f1', '#84cc16'];

$slicePath = function (float $cx, float $cy, float $r, float $start, float $end): string {
    if ($end - $start >= 360) {
        $top = $cy - $r;

        return sprintf(
            'M %.2f %.2f A %.2f %.2f 0 1 0 %.2f %.2f A %.2f %.2f 0 1 0 %.2f %.2f Z',
            $cx, $top,
            $r, $r, $cx, $top + ($r * 2),
            $r, $r, $cx, $top
        );
    }

    $radStart = deg2rad($start);
    $radEnd = deg2rad($end);

    $x1 = $cx + $r * cos($radStart);
    $y1 = $cy + $r * sin($radStart);
    $x2 = $cx + $r * cos($radEnd);
    $y2 = $cy + $r * sin($radEnd);

    $large = ($end - $start) > 180 ? 1 : 0;

    return sprintf(
        'M %.2f %.2f L %.2f %.2f A %.2f %.2f 0 %d 1 %.2f %.2f Z',
        $cx, $cy, $x1, $y1, $r, $r, $large, $x2, $y2
    );
};

$centerText = $centerLabel;

if ($centerText === null && $total > 0 && count($data) > 0) {
    $largest = collect($data)->sortByDesc('percentage')->first();
    $centerText = ($largest['percentage'] ?? 0).'%';
}
@endphp

<div class="grid grid-cols-1 items-center gap-6 md:grid-cols-2">
    <div class="mx-auto aspect-square w-full" style="max-width: {{ $size }}px;">
        <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
            @if($total > 0)
                @php $startAngle = 0; @endphp
                @foreach($data as $index => $item)
                    @php
                        $percentage = $total > 0 ? ((float) $item['value'] / $total) : 0;
                        $angle = $percentage * 360;
                        $color = $colors[$index % count($colors)] ?? '#94a3b8';
                    @endphp

                    @if($angle > 0 && $angle < 360)
                        <path
                            d="{{ $slicePath(50, 50, 45, $startAngle, $startAngle + $angle) }}"
                            fill="{{ $color }}"
                            stroke="#ffffff"
                            stroke-width="1"
                        />
                        @php $startAngle += $angle; @endphp
                    @elseif($angle >= 360)
                        <circle cx="50" cy="50" r="45" fill="{{ $color }}" />
                    @endif
                @endforeach

                @if($donut)
                    <circle cx="50" cy="50" r="28" fill="#ffffff" />
                @endif

                @if($donut && filled($centerText))
                    <text
                        x="50"
                        y="50"
                        text-anchor="middle"
                        dominant-baseline="central"
                        class="fill-slate-700 text-[14px] font-semibold"
                        transform="rotate(90 50 50)"
                    >{{ $centerText }}</text>
                @endif
            @else
                <circle cx="50" cy="50" r="45" fill="#e2e8f0" />
                @if($donut)
                    <circle cx="50" cy="50" r="28" fill="#ffffff" />
                @endif
            @endif
        </svg>
    </div>

    @if(count($data) > 0)
        <div class="space-y-4">
            @foreach($data as $index => $item)
                <div class="flex items-start gap-3">
                    <div
                        class="mt-1 size-3.5 shrink-0 rounded-sm"
                        style="background-color: {{ $colors[$index % count($colors)] ?? '#94a3b8' }}"
                    ></div>
                    <div class="flex-1 leading-tight">
                        <span class="block text-sm text-slate-500">{{ $item['name'] }}</span>
                        <span class="text-base font-semibold text-slate-900">{{ $item['label'] ?? number_format((float) ($item['value'] ?? 0), 2) }}</span>
                    </div>
                </div>
            @endforeach

            @if(filled($footer))
                <div class="pt-2 text-center text-xs text-slate-400">{{ $footer }}</div>
            @endif
        </div>
    @endif
</div>
