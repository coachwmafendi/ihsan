@props([
    'data' => [],
    'title' => 'Donations by Frequency',
    'description' => '',
    'canvasId' => 'stacked-bar-chart',
    'heightClass' => 'h-72',
])

<div
    {{ $attributes }}
    class="w-full {{ $heightClass }}"
    x-data="@js(['chartData' => $data, 'chartDescription' => $description])"
    x-init="renderStackedBarChart($el, chartData, chartDescription, @js($title))"
    x-effect="renderStackedBarChart($el, chartData, chartDescription, @js($title))"

>
    <canvas id="{{ $canvasId }}" class="h-full w-full"></canvas>
</div>
