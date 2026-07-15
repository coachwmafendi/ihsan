@props([
    'data' => [],
    'title' => 'Donations by Frequency',
    'description' => '',
    'oneTimeColor' => '#1e40af',
    'recurringColor' => '#60a5fa',
])

@php
$maxScale = (int) ($data['max_scale'] ?? 10);
$step = (int) ($data['step'] ?? 2);
$days = $data['days'] ?? [];
$dayCount = count($days);
$oneTimeTotal = $data['one_time_total'] ?? 0;
$recurringTotal = $data['recurring_total'] ?? 0;

$width = 800;
$height = 460;
$marginLeft = 60;
$marginRight = 20;
$marginTop = 150;
$marginBottom = 50;
$chartW = $width - $marginLeft - $marginRight;
$chartH = $height - $marginTop - $marginBottom;
$slotW = $dayCount > 0 ? $chartW / $dayCount : 0;
$barW = $slotW * 0.65;
$gap = $slotW * 0.35;
@endphp

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 {{ $width }} {{ $height }}"
    width="{{ $width }}"
    height="{{ $height }}"
>
    <style>
        text {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>

    <rect x="0" y="0" width="{{ $width }}" height="{{ $height }}" fill="#ffffff" />

    @if(filled($title))
        <text x="40" y="45" font-size="22" font-weight="bold" fill="#0f172a">{{ $title }}</text>
    @endif

    @if(filled($description))
        <text x="40" y="75" font-size="14" fill="#64748b">{{ $description }}</text>
    @endif

    <g transform="translate(40, 105)">
        <rect x="0" y="0" width="16" height="16" rx="3" fill="{{ $oneTimeColor }}" />
        <text x="24" y="8" dominant-baseline="middle" font-size="14" fill="#475569">One-time donations</text>
        <text x="180" y="8" dominant-baseline="middle" font-size="16" font-weight="bold" fill="#0f172a">{{ number_format($oneTimeTotal) }}</text>

        <rect x="240" y="0" width="16" height="16" rx="3" fill="{{ $recurringColor }}" />
        <text x="264" y="8" dominant-baseline="middle" font-size="14" fill="#475569">Recurring donations</text>
        <text x="420" y="8" dominant-baseline="middle" font-size="16" font-weight="bold" fill="#0f172a">{{ number_format($recurringTotal) }}</text>
    </g>

    @if($dayCount > 0)
        @foreach(range($maxScale, 0, -$step) as $gridLabel)
            @php
                $y = $marginTop + $chartH - ($maxScale > 0 ? ($gridLabel / $maxScale) * $chartH : 0);
            @endphp
            <line
                x1="{{ $marginLeft }}"
                y1="{{ $y }}"
                x2="{{ $width - $marginRight }}"
                y2="{{ $y }}"
                stroke="#f1f5f9"
                stroke-width="1"
            />
            <text
                x="{{ $marginLeft - 10 }}"
                y="{{ $y }}"
                text-anchor="end"
                dominant-baseline="middle"
                font-size="13"
                fill="#94a3b8"
            >{{ $gridLabel }}</text>
        @endforeach

        @foreach($days as $index => $day)
            @php
                $x = $marginLeft + ($index * $slotW) + ($gap / 2);
                $recurringH = $maxScale > 0 ? ($day['recurring'] / $maxScale) * $chartH : 0;
                $oneTimeH = $maxScale > 0 ? ($day['one_time'] / $maxScale) * $chartH : 0;
                $totalH = $recurringH + $oneTimeH;
                $recurringY = $marginTop + $chartH - $recurringH;
                $oneTimeY = $marginTop + $chartH - $totalH;
            @endphp

            <rect
                x="{{ $x }}"
                y="{{ $marginTop }}"
                width="{{ $barW }}"
                height="{{ $chartH }}"
                fill="#f1f5f9"
                rx="3"
            />

            @if($recurringH > 0)
                <rect
                    x="{{ $x }}"
                    y="{{ $recurringY }}"
                    width="{{ $barW }}"
                    height="{{ $recurringH }}"
                    fill="{{ $recurringColor }}"
                />
            @endif

            @if($oneTimeH > 0)
                <rect
                    x="{{ $x }}"
                    y="{{ $oneTimeY }}"
                    width="{{ $barW }}"
                    height="{{ $oneTimeH }}"
                    fill="{{ $oneTimeColor }}"
                />
            @endif

            <title>{{ $day['date'] }}: One-time {{ $day['one_time'] }}, Recurring {{ $day['recurring'] }}</title>
        @endforeach

        @foreach($days as $index => $day)
            @php
                $x = $marginLeft + ($index * $slotW) + ($slotW / 2);
                $showLabel = $dayCount <= 14 || $index % ceil($dayCount / 7) === 0;
            @endphp
            @if($showLabel)
                <text
                    x="{{ $x }}"
                    y="{{ $height - 20 }}"
                    text-anchor="middle"
                    dominant-baseline="hanging"
                    font-size="12"
                    fill="#94a3b8"
                >{{ $day['date'] }}</text>
            @endif
        @endforeach
    @else
        <text
            x="{{ $width / 2 }}"
            y="{{ ($height + $marginTop) / 2 }}"
            text-anchor="middle"
            dominant-baseline="middle"
            font-size="14"
            fill="#94a3b8"
        >No data</text>
    @endif
</svg>
