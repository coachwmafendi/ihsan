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

    public string $effectiveFeeRate = '0.00';

    public string $nominalFeeRate = '2.5';

    /**
     * @var array<int, array{name: string, donations: int, volume: string, fees: string}>
     */
    public array $revenueByOrganization = [];

    public function mount(): void
    {
        $this->nominalFeeRate = number_format($this->processingFeePercent(), 1);

        $succeeded = Donation::query()->where('status', DonationStatus::Succeeded);

        $totalVolume = (float) (clone $succeeded)->sum('gross_amount');
        $this->totalDonationVolume = number_format($totalVolume, 2, '.', '');
        $this->totalTransactions = (clone $succeeded)->count();

        $totalFeeAmount = (float) ProcessingFee::query()->sum('fee_amount');
        $this->totalProcessingFees = number_format($totalFeeAmount, 2, '.', '');

        $this->averageFeePerTransaction = $this->totalTransactions > 0
            ? number_format($totalFeeAmount / $this->totalTransactions, 2, '.', '')
            : '0.00';

        $this->effectiveFeeRate = $totalVolume > 0
            ? number_format(($totalFeeAmount / $totalVolume) * 100, 2, '.', '')
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

    private function processingFeePercent(): float
    {
        return (float) config('services.stripe.processing_fee_percent', 2.5);
    }
}
