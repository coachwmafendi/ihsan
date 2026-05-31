<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;

class DonationTrendChart extends ChartWidget
{
    protected ?string $heading = 'Donation Trend';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $startDate = now()->subMonths(11)->startOfMonth();

        $donations = Donation::query()
            ->where('status', 'succeeded')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('id, base_amount, created_at')
            ->get();

        $monthly = $donations->groupBy(fn ($d) => $d->created_at->format('Y-m'))
            ->map(fn ($group) => (float) $group->sum('base_amount'));

        $labels = collect(range(11, 0))->map(fn (int $i) => now()->subMonths($i)->format('M Y'));
        $data = collect(range(11, 0))->map(
            fn (int $i) => (float) ($monthly->get(now()->subMonths($i)->format('Y-m'), 0))
        );

        return [
            'datasets' => [
                [
                    'label' => 'Donation Volume (MYR)',
                    'data' => $data->values()->toArray(),
                    'borderColor' => '#292524',
                    'backgroundColor' => 'rgba(41, 37, 36, 0.1)',
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
