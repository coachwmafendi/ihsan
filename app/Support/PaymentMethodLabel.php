<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * How a payment method is written for a person to read.
 *
 * The stored values are Stripe's own keys, and capitalising the first letter of
 * one produced "Apple_pay" on the dashboard. These are brand names with their
 * own spelling, and the chart and the CSV export were each making up their own.
 */
class PaymentMethodLabel
{
    private const Labels = [
        'card' => 'Card',
        'apple_pay' => 'Apple Pay',
        'google_pay' => 'Google Pay',
        'link' => 'Link',
        'bank_transfer' => 'Bank Transfer',
        'fpx' => 'FPX',
        'duitnow' => 'DuitNow',
        'grabpay' => 'GrabPay',
        'grabpay_paylater' => 'GrabPay PayLater',
        'boost' => 'Boost',
        'tng' => 'Touch n Go',
        'shopeepay' => 'ShopeePay',
        'alipay' => 'Alipay',
        'wechatpay' => 'WeChat Pay',
        'paypal' => 'PayPal',
    ];

    public static function for(?string $type, string $fallback = 'Other'): string
    {
        $key = strtolower(trim((string) $type));

        if ($key === '') {
            return $fallback;
        }

        // An unknown method still reads better as words than as a database key.
        return self::Labels[$key] ?? Str::of($key)->replace('_', ' ')->title()->toString();
    }
}
