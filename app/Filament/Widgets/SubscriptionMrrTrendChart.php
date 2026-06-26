<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\SubscriptionSchedule;
use Filament\Widgets\ChartWidget;

class SubscriptionMrrTrendChart extends ChartWidget
{
    protected ?string $heading = 'Subscription MRR Trend';

    protected string $color = 'gray';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $startDate = now()->subMonths(11)->startOfMonth();

        $subscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('id, amount, interval, created_at')
            ->get();

        $monthly = $subscriptions->groupBy(fn ($s) => $s->created_at->format('Y-m'))
            ->map(fn ($group) => $group->sum(function ($s) {
                return $s->amount * SubscriptionSchedule::monthlyFactor($s->interval);
            }));

        $labels = collect(range(11, 0))->map(fn (int $i) => now()->subMonths($i)->format('M Y'));
        $data = collect(range(11, 0))->map(
            fn (int $i) => round((float) ($monthly->get(now()->subMonths($i)->format('Y-m'), 0)), 2)
        );

        return [
            'datasets' => [
                [
                    'label' => 'Estimated MRR (MYR)',
                    'data' => $data->values()->toArray(),
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
