<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use Filament\Widgets\ChartWidget;

class OrganizationGrowthChart extends ChartWidget
{
    protected ?string $heading = 'Organization Growth';

    protected string $color = 'gray';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $startDate = now()->subMonths(11)->startOfMonth();

        $countsBefore = Organization::query()
            ->where('created_at', '<', $startDate)
            ->count();

        $monthly = Organization::query()
            ->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(fn ($org) => $org->created_at->format('Y-m'))
            ->map(fn ($group) => $group->count());

        $labels = collect(range(11, 0))->map(fn (int $i) => now()->subMonths($i)->format('M Y'));

        $cumulative = collect(range(11, 0))->reduce(function (array $carry, int $i) use ($monthly) {
            $key = now()->subMonths($i)->format('Y-m');
            $carry['total'] += $monthly->get($key, 0);
            $carry['data'][] = $carry['total'];

            return $carry;
        }, ['total' => $countsBefore, 'data' => []]);

        return [
            'datasets' => [
                [
                    'label' => 'Total Organizations',
                    'data' => $cumulative['data'],
                    'fill' => false,
                    'tension' => 0.2,
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
