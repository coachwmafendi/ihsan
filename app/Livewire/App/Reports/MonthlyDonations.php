<?php

declare(strict_types=1);

namespace App\Livewire\App\Reports;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Report')]
class MonthlyDonations extends Component
{
    public string $selectedMonth = '';

    public bool $customRange = false;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->selectedMonth = today()->format('Y-m');
        $this->setDateRangeFromMonth();
    }

    public function updatedSelectedMonth(): void
    {
        $this->setDateRangeFromMonth();
    }

    public function updatedCustomRange(bool $value): void
    {
        if (! $value) {
            $this->setDateRangeFromMonth();
        }
    }

    private function setDateRangeFromMonth(): void
    {
        if (! Carbon::canBeCreatedFromFormat('Y-m', $this->selectedMonth)) {
            $this->selectedMonth = today()->format('Y-m');
        }

        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);

        $this->dateFrom = $date->copy()->startOfMonth()->toDateString();
        $this->dateTo = $date->copy()->endOfMonth()->toDateString();
    }

    #[Computed]
    public function organization(): ?Organization
    {
        return Auth::user()?->organization;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    #[Computed]
    public function dateRange(): array
    {
        $from = CarbonImmutable::parse($this->dateFrom ?: today()->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($this->dateTo ?: today()->toDateString())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function availableMonths(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        $firstDonationDate = Donation::query()
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->min('created_at');

        $start = $firstDonationDate !== null
            ? CarbonImmutable::parse($firstDonationDate)->startOfMonth()
            : today()->toImmutable()->subMonths(11)->startOfMonth();

        $end = today()->toImmutable()->startOfMonth();
        $months = [];

        while ($start->lte($end)) {
            $months[$start->format('Y-m')] = $start->format('F Y');
            $start = $start->addMonth();
        }

        return array_reverse($months, true);
    }

    /**
     * @return array{
     *     total_gross: float,
     *     processing_fee: float,
     *     net_received: float,
     *     total_donations: int,
     *     unique_donors: int,
     *     has_approximations: bool
     * }
     */
    #[Computed]
    public function summary(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [
                'total_gross' => 0.0,
                'processing_fee' => 0.0,
                'net_received' => 0.0,
                'total_donations' => 0,
                'unique_donors' => 0,
                'has_approximations' => false,
            ];
        }

        [$from, $to] = $this->dateRange;

        $donations = Donation::query()
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->where('donations.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('donations.created_at', '<=', $to));

        $result = (clone $donations)
            ->selectRaw('COALESCE(SUM('.Donation::reportAmountSql().'), 0) as total_gross')
            ->selectRaw('SUM(processing_fee + stripe_fee) as processing_fee')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as net_received')
            ->selectRaw('COUNT(*) as total_donations')
            ->selectRaw('COUNT(DISTINCT donor_id) as unique_donors')
            ->first();

        return [
            'total_gross' => (float) $result->total_gross,
            'processing_fee' => (float) $result->processing_fee,
            'net_received' => (float) $result->net_received,
            'total_donations' => (int) $result->total_donations,
            'unique_donors' => (int) $result->unique_donors,
            'has_approximations' => Donation::hasReportApproximations($donations),
        ];
    }

    #[Computed]
    public function campaignBreakdown(): Collection
    {
        $org = $this->organization;

        if (! $org) {
            return collect();
        }

        [$from, $to] = $this->dateRange;

        return Campaign::query()
            ->where('organization_id', $org->id)
            ->select('campaigns.*')
            ->withCount([
                'donations' => fn ($q) => $q
                    ->where('status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->where('created_at', '<=', $to)),
            ])
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->where('donations.created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->where('donations.created_at', '<=', $to))
                    ->selectRaw('COALESCE(SUM('.Donation::reportAmountSql().'), 0)'),
                'gross_amount'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->where('donations.created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->where('donations.created_at', '<=', $to))
                    ->selectRaw('SUM(donations.processing_fee + donations.stripe_fee)'),
                'processing_fee'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->where('donations.created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->where('donations.created_at', '<=', $to))
                    ->selectRaw('SUM(donations.net_amount)'),
                'net_amount'
            )
            ->orderByDesc('gross_amount')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.app.reports.monthly-donations');
    }
}
