<?php

namespace App\Filament\Widgets;

use App\Models\ProcessingFee;
use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class ProcessingFeeTrendChart extends ChartWidget
{
    protected ?string $heading = 'Processing Fee Trend';

    protected string $color = 'warning';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $period = ReportingPeriod::platform();
        $firstMonth = $period->localNow()->subMonths(11)->startOfMonth();

        // Every status counts: fees are 'collected' when paid upfront and
        // 'pending' when invoiced later, while 'paid' is only ever set by hand
        // on the Processing Fees page. Filtering to it drew a flat zero line.
        $fees = ProcessingFee::query()
            ->where('created_at', '>=', $firstMonth->utc())
            ->get(['id', 'fee_amount', 'created_at']);

        // Grouped from the raw stored value, which is UTC: a fee recorded at
        // 7am on the first of a month would otherwise land in the previous one.
        $monthly = $fees
            ->groupBy(fn (ProcessingFee $fee): string => $period
                ->toLocal(CarbonImmutable::parse($fee->getRawOriginal('created_at'), 'UTC'))
                ->format('Y-m'))
            ->map(fn ($group) => (float) $group->sum('fee_amount'));

        $months = collect(range(11, 0))->map(
            fn (int $i): CarbonImmutable => $period->localNow()->subMonths($i)->startOfMonth()
        );

        $labels = $months->map(fn (CarbonImmutable $month): string => $month->format('M Y'));
        $data = $months->map(
            fn (CarbonImmutable $month): float => (float) $monthly->get($month->format('Y-m'), 0)
        );

        return [
            'datasets' => [
                [
                    'label' => 'Processing Fees (MYR)',
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
