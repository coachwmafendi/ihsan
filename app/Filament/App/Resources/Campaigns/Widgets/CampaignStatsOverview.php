<?php

namespace App\Filament\App\Resources\Campaigns\Widgets;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class CampaignStatsOverview extends BaseWidget
{
    protected string $view = 'filament.app.resources.campaigns.widgets.campaign-stats-overview';

    public int $totalCampaigns = 0;

    public int $liveCampaigns = 0;

    public string $totalRaised = '0.00';

    public int $totalDonors = 0;

    public function mount(): void
    {
        $orgId = auth()->user()->organization_id;

        $this->totalCampaigns = Campaign::where('organization_id', $orgId)->count();
        $this->liveCampaigns = Campaign::where('organization_id', $orgId)
            ->where('status', CampaignStatus::Active)
            ->count();
        $this->totalRaised = number_format(
            Donation::where('status', DonationStatus::Succeeded)
                ->whereHas('campaign', fn ($q) => $q->where('organization_id', $orgId))
                ->sum('base_amount'),
            2
        );
        $this->totalDonors = Donation::where('status', DonationStatus::Succeeded)
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $orgId))
            ->whereNotNull('donor_id')
            ->distinct('donor_id')
            ->count('donor_id');
    }
}
