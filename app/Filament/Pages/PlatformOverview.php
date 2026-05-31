<?php

namespace App\Filament\Pages;

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\Subscription;
use Filament\Pages\Page;

class PlatformOverview extends Page
{
    protected string $view = 'filament.admin.pages.platform-overview';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = 5;

    public int $totalOrganizations = 0;

    public int $pendingOrganizations = 0;

    public int $activeOrganizations = 0;

    public int $suspendedOrganizations = 0;

    public int $newOrganizationsThisMonth = 0;

    public int $stripeOnboardedOrganizations = 0;

    public string $totalDonationsVolume = '0.00';

    public int $totalDonationsCount = 0;

    public string $totalProcessingFees = '0.00';

    public int $activeSubscriptions = 0;

    public int $totalDonors = 0;

    /**
     * @var array<int, array{name: string, email: string, status: string, created_at: string}>
     */
    public array $recentOrganizations = [];

    /**
     * @var array<int, array{organization: string, campaign: string, amount: string, status: string}>
     */
    public array $recentDonations = [];

    /**
     * @var array<int, array{name: string, total: string}>
     */
    public array $topOrganizations = [];

    public function mount(): void
    {
        $this->totalOrganizations = Organization::query()->count();
        $this->pendingOrganizations = Organization::query()->where('status', 'pending')->count();
        $this->activeOrganizations = Organization::query()->where('status', 'active')->count();
        $this->suspendedOrganizations = Organization::query()->where('status', 'suspended')->count();
        $this->newOrganizationsThisMonth = Organization::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $this->stripeOnboardedOrganizations = Organization::query()
            ->where('stripe_onboarded', true)
            ->count();

        $succeededDonations = Donation::query()->where('status', DonationStatus::Succeeded);

        $this->totalDonationsVolume = number_format((float) (clone $succeededDonations)->sum('base_amount'), 2, '.', '');
        $this->totalDonationsCount = Donation::query()->count();

        $this->totalProcessingFees = number_format((float) ProcessingFee::query()
            ->where('status', 'paid')
            ->sum('fee_amount'), 2, '.', '');

        $this->activeSubscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $this->totalDonors = Donor::query()->count();

        $this->recentOrganizations = Organization::query()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Organization $org): array => [
                'name' => $org->name,
                'email' => $org->contact_email ?? '—',
                'status' => $org->status->value,
                'created_at' => $org->created_at->diffForHumans(),
            ])
            ->all();

        $this->topOrganizations = Organization::query()
            ->select('organizations.name')
            ->selectRaw('COALESCE(SUM(CASE WHEN donations.status = ? THEN donations.base_amount ELSE 0 END), 0) as total', [DonationStatus::Succeeded->value])
            ->leftJoin('campaigns', 'organizations.id', '=', 'campaigns.organization_id')
            ->leftJoin('donations', 'campaigns.id', '=', 'donations.campaign_id')
            ->groupBy('organizations.id', 'organizations.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn (Organization $org): array => [
                'name' => $org->name,
                'total' => 'MYR '.number_format((float) $org->total, 2, '.', ''),
            ])
            ->all();

        $this->recentDonations = Donation::query()
            ->with(['campaign:id,title,organization_id', 'campaign.organization:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Donation $donation): array {
                $amount = $donation->currency !== 'myr' && $donation->base_amount !== null
                    ? '≈ MYR '.number_format((float) $donation->base_amount, 2, '.', '')
                    : 'MYR '.number_format((float) $donation->gross_amount, 2, '.', '');

                $original = $donation->currency !== 'myr' && $donation->base_amount !== null
                    ? strtoupper($donation->currency).' '.number_format((float) $donation->gross_amount, 2, '.', '')
                    : null;

                return [
                    'organization' => $donation->campaign->organization->name,
                    'campaign' => $donation->campaign->title,
                    'amount' => $amount,
                    'original_amount' => $original,
                    'status' => $donation->status->value,
                ];
            })
            ->all();
    }
}
