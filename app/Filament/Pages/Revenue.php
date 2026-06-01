<?php

namespace App\Filament\Pages;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class Revenue extends Page
{
    protected string $view = 'filament.admin.pages.revenue';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Revenue';

    protected static ?int $navigationSort = 20;

    public string $period = 'today';

    public string $totalProcessingFees = '0.00';

    public string $paidFees = '0.00';

    public string $pendingFees = '0.00';

    public string $collectedFees = '0.00';

    public string $invoicedFees = '0.00';

    public string $failedFees = '0.00';

    public int $totalTransactions = 0;

    public string $totalDonationVolume = '0.00';

    public string $averageDonationSize = '0.00';

    public string $averageFeePerTransaction = '0.00';

    public string $effectiveFeeRate = '0.00';

    public string $nominalFeeRate = '2.5';

    /**
     * @var array<int, array{name: string, donations: int, volume: string, fees: string, avg_donation: string, effective_rate: string}>
     */
    public array $revenueByOrganization = [];

    public function mount(): void
    {
        $this->nominalFeeRate = number_format($this->processingFeePercent(), 1);
        $this->calculate();
    }

    public function updatedPeriod(): void
    {
        $this->calculate();
    }

    public function calculate(): void
    {
        [$from, $to] = $this->dateRange();

        $succeeded = Donation::query()
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn (Builder $q) => $q->whereDate('donations.created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('donations.created_at', '<=', $to));

        $totalVolume = (float) (clone $succeeded)->sum('base_amount');
        $this->totalDonationVolume = number_format($totalVolume, 2, '.', '');
        $this->totalTransactions = (clone $succeeded)->count();
        $this->averageDonationSize = $this->totalTransactions > 0
            ? number_format($totalVolume / $this->totalTransactions, 2, '.', '')
            : '0.00';

        $totalFeeAmount = (float) ProcessingFee::query()
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to))
            ->sum('fee_amount');
        $this->totalProcessingFees = number_format($totalFeeAmount, 2, '.', '');

        $this->paidFees = $this->sumFeesByStatus('paid', $from, $to);
        $this->collectedFees = $this->sumFeesByStatus('collected', $from, $to);
        $this->pendingFees = $this->sumFeesByStatus('pending', $from, $to);
        $this->invoicedFees = $this->sumFeesByStatus('invoiced', $from, $to);
        $this->failedFees = $this->sumFeesByStatus('failed', $from, $to);

        $this->averageFeePerTransaction = $this->totalTransactions > 0
            ? number_format($totalFeeAmount / $this->totalTransactions, 2, '.', '')
            : '0.00';

        $this->effectiveFeeRate = $totalVolume > 0
            ? number_format(($totalFeeAmount / $totalVolume) * 100, 2, '.', '')
            : '0.00';

        $succeededDonations = Donation::query()
            ->selectRaw('campaigns.organization_id, COUNT(*) as donation_count, SUM(base_amount) as volume, AVG(base_amount) as avg_donation')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('donations.status', DonationStatus::Succeeded)
            ->when($from, fn (Builder $q) => $q->whereDate('donations.created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('donations.created_at', '<=', $to))
            ->groupBy('campaigns.organization_id')
            ->get()
            ->keyBy('organization_id');

        $feesByOrg = ProcessingFee::query()
            ->selectRaw('organization_id, SUM(fee_amount) as total_fees')
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to))
            ->groupBy('organization_id')
            ->get()
            ->keyBy('organization_id');

        $organizations = Organization::query()->get();

        $this->revenueByOrganization = $organizations
            ->map(function (Organization $org) use ($succeededDonations, $feesByOrg) {
                $orgDonations = $succeededDonations->get($org->id);
                $orgFees = $feesByOrg->get($org->id);
                $volume = (float) ($orgDonations?->volume ?? 0);
                $fees = (float) ($orgFees?->total_fees ?? 0);

                return [
                    'name' => $org->name,
                    'donations' => (int) ($orgDonations?->donation_count ?? 0),
                    'volume' => 'MYR '.number_format($volume, 2, '.', ''),
                    'fees' => 'MYR '.number_format($fees, 2, '.', ''),
                    'avg_donation' => $orgDonations && $orgDonations->donation_count > 0
                        ? 'MYR '.number_format((float) $orgDonations->avg_donation, 2, '.', '')
                        : 'MYR 0.00',
                    'effective_rate' => $volume > 0
                        ? number_format(($fees / $volume) * 100, 2, '.', '').'%'
                        : '0.00%',
                ];
            })
            ->filter(fn (array $row) => $row['donations'] > 0)
            ->sortByDesc('donations')
            ->values()
            ->all();
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function dateRange(): array
    {
        return match ($this->period) {
            'today' => [today()->toDateString(), today()->toDateString()],
            'yesterday' => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'last_7_days' => [today()->subDays(6)->toDateString(), today()->toDateString()],
            'last_30_days' => [today()->subDays(29)->toDateString(), today()->toDateString()],
            'last_90_days' => [today()->subDays(89)->toDateString(), today()->toDateString()],
            'this_week' => [today()->startOfWeek()->toDateString(), today()->endOfWeek()->toDateString()],
            'this_month' => [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()],
            'this_year' => [today()->startOfYear()->toDateString(), today()->endOfYear()->toDateString()],
            default => [null, null],
        };
    }

    private function processingFeePercent(): float
    {
        return (float) config('services.stripe.processing_fee_percent', 2.5);
    }

    private function sumFeesByStatus(string $status, ?string $from, ?string $to): string
    {
        $sum = (float) ProcessingFee::query()
            ->where('status', $status)
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to))
            ->sum('fee_amount');

        return number_format($sum, 2, '.', '');
    }
}
