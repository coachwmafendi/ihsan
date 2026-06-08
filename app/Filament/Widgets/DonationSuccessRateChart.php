<?php

namespace App\Filament\Widgets;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Filament\Widgets\ChartWidget;

class DonationSuccessRateChart extends ChartWidget
{
    protected ?string $heading = 'Donation Success Rate';

    protected string $color = 'gray';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $startDate = now()->subMonths(11)->startOfMonth();

        $donations = Donation::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('status, created_at')
            ->get();

        $byMonth = $donations->groupBy(fn ($d) => $d->created_at->format('Y-m'));

        $labels = collect(range(11, 0))->map(fn (int $i) => now()->subMonths($i)->format('M Y'));
        $data = collect(range(11, 0))->map(function (int $i) use ($byMonth) {
            $key = now()->subMonths($i)->format('Y-m');
            $group = $byMonth->get($key);

            if (! $group || $group->isEmpty()) {
                return null;
            }

            $total = $group->count();
            $succeeded = $group->where('status', DonationStatus::Succeeded)->count();

            return round(($succeeded / $total) * 100, 1);
        });

        return [
            'datasets' => [
                [
                    'label' => 'Success Rate (%)',
                    'data' => $data->values()->toArray(),
                    'fill' => false,
                    'tension' => 0.3,
                    'spanGaps' => true,
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
