<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    #[Computed]
    public function organization()
    {
        return Auth::user()?->organization;
    }

    #[Computed]
    public function stats(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        $totalDonationsQuery = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded);

        $totalDonations = (float) (clone $totalDonationsQuery)->sum(Donation::reportAmountColumn());

        $donationCount = (clone $totalDonationsQuery)->count();

        $donorCount = Donor::whereHas('donations.campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->distinct()
            ->count('donors.id');

        $campaignCount = Campaign::where('organization_id', $org->id)->count();

        return [
            [
                'label' => 'Total Donations',
                'value' => (Donation::hasReportApproximations(clone $totalDonationsQuery) ? '≈ ' : '').'MYR '.number_format((float) $totalDonations, 2),
                'trend' => '+12%',
                'trendUp' => true,
                'sparkline' => $this->donationSparkline(),
            ],
            [
                'label' => 'Donations',
                'value' => number_format($donationCount),
                'trend' => '+5%',
                'trendUp' => true,
                'sparkline' => [],
            ],
            [
                'label' => 'Donors',
                'value' => number_format($donorCount),
                'trend' => '+8%',
                'trendUp' => true,
                'sparkline' => [],
            ],
            [
                'label' => 'Campaigns',
                'value' => number_format($campaignCount),
                'trend' => null,
                'trendUp' => null,
                'sparkline' => [],
            ],
        ];
    }

    #[Computed]
    public function donationSparkline(): array
    {
        $org = $this->organization;
        if (! $org) {
            return [];
        }

        $data = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $amount = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
                ->where('status', DonationStatus::Succeeded)
                ->whereDate('created_at', $date)
                ->sum(Donation::reportAmountColumn());
            $data[] = (int) $amount;
        }

        return $data;
    }

    #[Computed]
    public function recentDonations()
    {
        $org = $this->organization;
        if (! $org) {
            return collect();
        }

        return Donation::with('donor')
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function topCampaigns()
    {
        $org = $this->organization;
        if (! $org) {
            return collect();
        }

        return Campaign::where('organization_id', $org->id)
            ->select('campaigns.*')
            ->withCount(['donations' => fn ($q) => $q->where('status', DonationStatus::Succeeded)])
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->select(Donation::reportSumColumn()),
                'report_amount'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.currency', '!=', 'myr')
                    ->whereNotNull('donations.base_amount')
                    ->selectRaw('COUNT(*) > 0'),
                'has_report_approximation'
            )
            ->orderByDesc('report_amount')
            ->limit(5)
            ->get();
    }

    public function openCreateCampaignModal(): void
    {
        $this->dispatch('open-create-campaign-modal');
    }

    public function render()
    {
        return view('livewire.app.dashboard', [
            'title' => 'Dashboard',
        ]);
    }
}
