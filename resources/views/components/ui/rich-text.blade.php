@props([
    'text' => '',
])

@php
$escaped = e($text);
$escaped = (string) preg_replace(
    '~(https?://[^\s<]+)~',
    '<a href="$1" target="_blank" rel="noopener noreferrer" class="font-medium text-teal-600 hover:text-teal-700 hover:underline">$1</a>',
    $escaped
);
@endphp

<div {{ $attributes->merge(['class' => 'text-sm text-slate-900 whitespace-normal']) }}>
    {!! nl2br($escaped) !!}
</div>
