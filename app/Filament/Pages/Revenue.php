<?php

namespace App\Filament\Pages;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\PlatformFee;
use Filament\Pages\Page;

class Revenue extends Page
{
    protected string $view = 'filament.admin.pages.revenue';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Revenue';

    protected static ?int $navigationSort = 20;

    public string $totalPlatformFees = '0.00';

    public int $totalTransactions = 0;

    public string $totalDonationVolume = '0.00';

    public string $averageFeePerTransaction = '0.00';

    /**
     * @var array<int, array{name: string, donations: int, volume: string, fees: string}>
     */
    public array $revenueByOrganization = [];

    public function mount(): void
    {
        $this->totalPlatformFees = number_format((float) PlatformFee::query()
            ->where('status', 'transferred')
            ->sum('fee_amount'), 2, '.', '');

        $succeeded = Donation::query()->where('status', DonationStatus::Succeeded);

        $this->totalDonationVolume = number_format((float) (clone $succeeded)->sum('gross_amount'), 2, '.', '');
        $this->totalTransactions = (clone $succeeded)->count();

        $this->averageFeePerTransaction = $this->totalTransactions > 0
            ? number_format((float) PlatformFee::query()
                ->where('status', 'transferred')
                ->avg('fee_amount'), 2, '.', '')
            : '0.00';

        $organizations = Organization::query()->get();

        $this->revenueByOrganization = $organizations
            ->map(fn (Organization $org): array => [
                'name' => $org->name,
                'donations' => Donation::query()
                    ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
                    ->where('status', DonationStatus::Succeeded)
                    ->count(),
                'volume' => 'MYR '.number_format((float) Donation::query()
                    ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
                    ->where('status', DonationStatus::Succeeded)
                    ->sum('gross_amount'), 2, '.', ''),
                'fees' => 'MYR '.number_format((float) PlatformFee::query()
                    ->where('organization_id', $org->id)
                    ->where('status', 'transferred')
                    ->sum('fee_amount'), 2, '.', ''),
            ])
            ->filter(fn (array $row) => $row['donations'] > 0)
            ->sortByDesc('donations')
            ->values()
            ->all();
    }
}
