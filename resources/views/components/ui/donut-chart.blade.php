@props([
    'data' => [],
    'title' => 'Chart',
    'description' => '',
    'canvasId' => 'donut-chart',
    'heightClass' => 'h-64',
])

<div
    {{ $attributes }}
    class="w-full {{ $heightClass }}"
    x-data="@js(['chartData' => $data, 'chartDescription' => $description])"
    x-init="renderDonutChart($el, chartData, chartDescription, @js($title))"
    x-effect="renderDonutChart($el, chartData, chartDescription, @js($title))"

>
    <canvas id="{{ $canvasId }}" class="h-full w-full"></canvas>
</div>
