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
    x-data="{
        chartData: @js($data),
        chartDescription: @js($description),
        visible: false,
        rendered: false,
    }"
    x-init="const observer = new IntersectionObserver((entries) => { if (entries[0].isIntersecting) this.visible = true; }, { threshold: 0.2, rootMargin: '0px 0px -40px 0px' }); observer.observe($el);"
    x-effect="if (visible && ! rendered) { rendered = true; renderStackedBarChart($el, chartData, chartDescription, @js($title)); }"
>
    <canvas id="{{ $canvasId }}" class="h-full w-full"></canvas>
</div>
