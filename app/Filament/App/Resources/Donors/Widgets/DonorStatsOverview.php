<?php

namespace App\Filament\App\Resources\Donors\Widgets;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Donor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class DonorStatsOverview extends BaseWidget
{
    protected string $view = 'filament.app.resources.donors.widgets.donor-stats-overview';

    public int $totalDonors = 0;

    public int $newThisMonth = 0;

    public string $totalDonated = '0.00';

    public int $activeRecurring = 0;

    public function mount(): void
    {
        $orgId = auth()->user()->organization_id;

        $this->totalDonors = Donor::whereHas('donations.campaign', fn ($q) => $q->where('organization_id', $orgId))->count();

        $this->newThisMonth = Donor::whereHas('donations.campaign', fn ($q) => $q->where('organization_id', $orgId))
            ->whereHas('donations', fn ($q) => $q->whereDate('created_at', '>=', now()->startOfMonth()))
            ->count();

        $this->totalDonated = number_format(
            Donation::where('status', DonationStatus::Succeeded)
                ->whereHas('campaign', fn ($q) => $q->where('organization_id', $orgId))
                ->sum('base_amount'),
            2
        );

        $this->activeRecurring = Donor::whereHas('subscriptions.campaign', fn ($q) => $q->where('organization_id', $orgId))
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'active'))
            ->count();
    }
}
