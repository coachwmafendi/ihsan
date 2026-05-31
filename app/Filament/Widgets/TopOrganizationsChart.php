<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopOrganizationsChart extends ChartWidget
{
    protected ?string $heading = 'Top Organizations by Volume';

    protected string $color = 'gray';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $orgs = Donation::query()
            ->selectRaw('SUM(base_amount) as total, organizations.name')
            ->join('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
            ->join('organizations', 'organizations.id', '=', 'campaigns.organization_id')
            ->where('donations.status', 'succeeded')
            ->groupBy('organizations.id', 'organizations.name')
            ->orderByDesc(DB::raw('SUM(base_amount)'))
            ->limit(10)
            ->pluck('total', 'name');

        return [
            'datasets' => [
                [
                    'label' => 'Donation Volume (MYR)',
                    'data' => $orgs->values()->toArray(),
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $orgs->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
