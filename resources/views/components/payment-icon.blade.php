@php
    $brand = strtolower((string) ($attributes->get('brand') ?? 'default'));
    $brand = str_replace(['_', ' ', '-'], '', $brand);

    $map = [
        'visa' => 'visa',
        'mastercard' => 'mastercard',
        'amex' => 'amex',
        'americanexpress' => 'amex',
        'discover' => 'discover',
        'diners' => 'diners',
        'dinersclub' => 'diners',
        'jcb' => 'jcb',
        'maestro' => 'maestro',
        'unionpay' => 'unionpay',
        'paypal' => 'paypal',
        'alipay' => 'alipay',
    ];

    $icon = $map[$brand] ?? 'default';
    $class = $attributes->get('class') ?? 'h-6 w-auto';

    $path = resource_path("svg/payment-icons/{$icon}.svg");

    if (! file_exists($path)) {
        $path = resource_path('svg/payment-icons/default.svg');
    }

    $svg = file_exists($path) ? file_get_contents($path) : null;

    if ($svg !== null) {
        $svg = preg_replace('/(<svg[^>]*?)\swidth="[^"]*"/', '$1', $svg, 1);
        $svg = preg_replace('/(<svg[^>]*?)\sheight="[^"]*"/', '$1', $svg, 1);
        $svg = preg_replace('/<svg\b/i', '<svg class="'.e($class).'" ', $svg, 1);
    }
@endphp

@if ($svg)
    {!! $svg !!}
@else
    <span {{ $attributes->merge(['class' => $class]) }}>{{ strtoupper($brand) }}</span>
@endif
