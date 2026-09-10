<?php

namespace App\Filament\Widgets;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Support\PaymentMethodLabel;
use Filament\Widgets\ChartWidget;

/**
 * How donors actually paid.
 *
 * This counted card brands and called them payment methods, which put every
 * Apple Pay and Google Pay donation inside the Visa and Mastercard slices -
 * a wallet payment carries a card network underneath it. The one thing the
 * chart was named for was the one thing it could not show.
 *
 * Grouping by type instead means anything new arrives on its own: FPX through
 * CHIP, or a wallet we have not switched on yet, without a line of code here.
 */
class PaymentMethodChart extends ChartWidget
{
    protected ?string $heading = 'Payment Methods';

    protected ?string $pollingInterval = null;

    /**
     * Wallets keep their own brand colours; everything else takes a neutral
     * one, so a method we have never seen before still gets a readable slice.
     */
    private const Colors = [
        'card' => '#3b82f6',
        'apple_pay' => '#0f172a',
        'google_pay' => '#ef4444',
        'link' => '#00d66f',
        'fpx' => '#10b981',
        'duitnow' => '#f97316',
        'grabpay' => '#059669',
        'tng' => '#2563eb',
        'boost' => '#e11d48',
        'shopeepay' => '#f43f5e',
    ];

    private const FallbackColor = '#78716c';

    protected function getData(): array
    {
        $methods = Donation::query()
            ->selectRaw("COALESCE(NULLIF(payment_method_type, ''), 'unknown') as method, COUNT(*) as count")
            ->where('status', DonationStatus::Succeeded)
            ->groupBy('method')
            ->pluck('count', 'method')
            ->sortDesc();

        return [
            'datasets' => [
                [
                    'data' => $methods->values()->toArray(),
                    'backgroundColor' => $methods->keys()
                        ->map(fn (string $method) => self::Colors[$method] ?? self::FallbackColor)
                        ->toArray(),
                    'borderColor' => 'var(--gray-50)',
                    'borderWidth' => 2,
                ],
            ],
            // The same spelling the donation pages use: "Apple Pay", not
            // "Apple_pay".
            'labels' => $methods->keys()
                ->map(fn (string $method) => PaymentMethodLabel::for($method, 'Unknown'))
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
