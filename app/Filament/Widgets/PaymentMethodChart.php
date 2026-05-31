<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;

class PaymentMethodChart extends ChartWidget
{
    protected ?string $heading = 'Payment Methods';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $methods = Donation::query()
            ->selectRaw('COALESCE(NULLIF(payment_method_brand, \'\'), \'unknown\') as brand, COUNT(*) as count')
            ->where('status', 'succeeded')
            ->groupBy('brand')
            ->pluck('count', 'brand')
            ->sortDesc();

        $colors = [
            'visa' => '#3b82f6',
            'mastercard' => '#ef4444',
            'amex' => '#10b981',
            'unknown' => '#78716c',
        ];

        return [
            'datasets' => [
                [
                    'data' => $methods->values()->toArray(),
                    'backgroundColor' => $methods->keys()->map(
                        fn (string $brand) => $colors[$brand] ?? '#78716c'
                    )->toArray(),
                    'borderColor' => 'var(--gray-50)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $methods->keys()->map(
                fn (string $brand) => str($brand)->headline()->toString()
            )->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
