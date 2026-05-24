<?php

namespace App\Filament\Pages;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Filament\Pages\Page;

class Revenue extends Page
{
    protected string $view = 'filament.admin.pages.revenue';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Revenue';

    protected static ?int $navigationSort = 20;

    public string $totalProcessingFees = '0.00';

    public int $totalTransactions = 0;

    public string $totalDonationVolume = '0.00';

    public string $averageFeePerTransaction = '0.00';

    /**
     * @var array<int, array{name: string, donations: int, volume: string, fees: string}>
     */
    public array $revenueByOrganization = [];

    public function mount(): void
    {
        $this->totalProcessingFees = number_format((float) ProcessingFee::query()
            ->where('status', 'paid')
            ->sum('fee_amount'), 2, '.', '');

        $succeeded = Donation::query()->where('status', DonationStatus::Succeeded);

        $this->totalDonationVolume = number_format((float) (clone $succeeded)->sum('gross_amount'), 2, '.', '');
        $this->totalTransactions = (clone $succeeded)->count();

        $this->averageFeePerTransaction = $this->totalTransactions > 0
            ? number_format((float) ProcessingFee::query()
                ->where('status', 'paid')
                ->avg('fee_amount'), 2, '.', '')
            : '0.00';

        $succeededDonations = Donation::query()
            ->selectRaw('campaigns.organization_id, COUNT(*) as donation_count, SUM(gross_amount) as volume')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('donations.status', DonationStatus::Succeeded)
            ->groupBy('campaigns.organization_id')
            ->get()
            ->keyBy('organization_id');

        $feesByOrg = ProcessingFee::query()
            ->selectRaw('organization_id, SUM(fee_amount) as total_fees')
            ->where('status', 'paid')
            ->groupBy('organization_id')
            ->get()
            ->keyBy('organization_id');

        $organizations = Organization::query()->get();

        $this->revenueByOrganization = $organizations
            ->map(fn (Organization $org): array => [
                'name' => $org->name,
                'donations' => (int) ($succeededDonations->get($org->id)?->donation_count ?? 0),
                'volume' => 'MYR '.number_format((float) ($succeededDonations->get($org->id)?->volume ?? 0), 2, '.', ''),
                'fees' => 'MYR '.number_format((float) ($feesByOrg->get($org->id)?->total_fees ?? 0), 2, '.', ''),
            ])
            ->filter(fn (array $row) => $row['donations'] > 0)
            ->sortByDesc('donations')
            ->values()
            ->all();
    }
}
