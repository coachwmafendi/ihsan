<?php

namespace App\Filament\Pages;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\ProcessingFee;
use App\Services\RevenueReportService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class Revenue extends Page
{
    protected string $view = 'filament.admin.pages.revenue';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Revenue';

    protected static ?int $navigationSort = 20;

    protected ?RevenueReportService $reportService = null;

    public string $period = 'this_month';

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
     * @var array<int, array{
     *     id: int,
     *     public_id: string|null,
     *     name: string,
     *     donations: int,
     *     volume_raw: float,
     *     volume: string,
     *     stripe_fees_raw: float,
     *     stripe_fees: string,
     *     fees_raw: float,
     *     fees: string,
     *     avg_donation_raw: float,
     *     avg_donation: string,
     *     effective_rate_raw: float,
     *     effective_rate: string,
     * }>
     */
    public array $revenueByOrganization = [];

    public function mount(): void
    {
        $this->reportService = app(RevenueReportService::class);
        $this->nominalFeeRate = number_format($this->processingFeePercent(), 1);
        $this->calculate();
    }

    public function updatedPeriod(): void
    {
        $this->calculate();
    }

    public function calculate(): void
    {
        $this->reportService ??= app(RevenueReportService::class);

        [$from, $to] = $this->reportService->dateRange($this->period);

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

        $this->revenueByOrganization = $this->reportService->organizationRows($this->period);
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
