{{-- resources/views/components/ui/sparkline.blade.php --}}
@props([
    'data' => [],
    'width' => 100,
    'height' => 32,
    'color' => '#0d9488',
    'fill' => true,
])

@php
if (empty($data)) {
    echo '<div class="w-[100px] h-[32px]"></div>';
    return;
}

$min = min($data);
$max = max($data);
$range = $max - $min;
if ($range === 0) $range = 1;

$padding = 2;
$chartWidth = $width - ($padding * 2);
$chartHeight = $height - ($padding * 2);

$points = [];
$count = count($data);
foreach ($data as $i => $value) {
    $x = $padding + ($i / ($count - 1)) * $chartWidth;
    $y = $padding + $chartHeight - (($value - $min) / $range) * $chartHeight;
    $points[] = [$x, $y];
}

$pathD = 'M ' . $points[0][0] . ' ' . $points[0][1];
for ($i = 1; $i < count($points); $i++) {
    $pathD .= ' L ' . $points[$i][0] . ' ' . $points[$i][1];
}

if ($fill) {
    $fillD = $pathD . ' L ' . $points[count($points)-1][0] . ' ' . ($height - $padding) . ' L ' . $points[0][0] . ' ' . ($height - $padding) . ' Z';
}
@endphp

<svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none">
    @if($fill)
        <defs>
            <linearGradient id="sparklineGradient-{{ uniqid() }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.15"/>
                <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0"/>
            </linearGradient>
        </defs>
        <path d="{{ $fillD }}" fill="url(#sparklineGradient-{{ uniqid() }})"/>
    @endif
    <path d="{{ $pathD }}" fill="none" stroke="{{ $color }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
