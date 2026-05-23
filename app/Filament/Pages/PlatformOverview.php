<?php

namespace App\Filament\Pages;

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\PlatformFee;
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

    public string $totalDonationsVolume = '0.00';

    public int $totalDonationsCount = 0;

    public string $totalPlatformFees = '0.00';

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

    public function mount(): void
    {
        $this->totalOrganizations = Organization::query()->count();
        $this->pendingOrganizations = Organization::query()->where('status', 'pending')->count();
        $this->activeOrganizations = Organization::query()->where('status', 'active')->count();
        $this->suspendedOrganizations = Organization::query()->where('status', 'suspended')->count();

        $succeededDonations = Donation::query()->where('status', DonationStatus::Succeeded);

        $this->totalDonationsVolume = number_format((float) (clone $succeededDonations)->sum('gross_amount'), 2, '.', '');
        $this->totalDonationsCount = Donation::query()->count();

        $this->totalPlatformFees = number_format((float) PlatformFee::query()
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

        $this->recentDonations = Donation::query()
            ->with(['campaign:id,title,organization_id', 'campaign.organization:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Donation $donation): array => [
                'organization' => $donation->campaign->organization->name,
                'campaign' => $donation->campaign->title,
                'amount' => 'MYR '.number_format((float) $donation->gross_amount, 2, '.', ''),
                'status' => $donation->status->value,
            ])
            ->all();
    }
}
