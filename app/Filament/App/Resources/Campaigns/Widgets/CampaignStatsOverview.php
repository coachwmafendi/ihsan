<?php

namespace App\Filament\App\Resources\Campaigns\Widgets;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $orgId = auth()->user()->organization_id;

        $totalCampaigns = Campaign::where('organization_id', $orgId)->count();
        $liveCampaigns = Campaign::where('organization_id', $orgId)
            ->where('status', CampaignStatus::Active)
            ->count();
        $totalRaised = Donation::where('status', DonationStatus::Succeeded)
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $orgId))
            ->sum('base_amount');
        $totalDonors = Donation::where('status', DonationStatus::Succeeded)
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $orgId))
            ->whereNotNull('donor_id')
            ->distinct('donor_id')
            ->count('donor_id');

        return [
            Stat::make('Total Campaigns', $totalCampaigns)
                ->description('All campaigns')
                ->color('gray'),
            Stat::make('Live Campaigns', $liveCampaigns)
                ->description('Active campaigns')
                ->color('success'),
            Stat::make('Total Raised', 'MYR '.number_format($totalRaised, 2))
                ->description('From paid donations')
                ->color('warning'),
            Stat::make('Total Donors', $totalDonors)
                ->description('Unique donors')
                ->color('info'),
        ];
    }
}
