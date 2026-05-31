<?php

namespace App\Filament\Widgets;

use App\Models\ProcessingFee;
use Filament\Widgets\ChartWidget;

class ProcessingFeeTrendChart extends ChartWidget
{
    protected ?string $heading = 'Processing Fee Trend';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $startDate = now()->subMonths(11)->startOfMonth();

        $fees = ProcessingFee::query()
            ->where('status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('id, fee_amount, created_at')
            ->get();

        $monthly = $fees->groupBy(fn ($d) => $d->created_at->format('Y-m'))
            ->map(fn ($group) => (float) $group->sum('fee_amount'));

        $labels = collect(range(11, 0))->map(fn (int $i) => now()->subMonths($i)->format('M Y'));
        $data = collect(range(11, 0))->map(
            fn (int $i) => (float) ($monthly->get(now()->subMonths($i)->format('Y-m'), 0))
        );

        return [
            'datasets' => [
                [
                    'label' => 'Processing Fees (MYR)',
                    'data' => $data->values()->toArray(),
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
