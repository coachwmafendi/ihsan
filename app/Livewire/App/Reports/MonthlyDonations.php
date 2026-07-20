<?php

declare(strict_types=1);

namespace App\Livewire\App\Reports;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Monthly Donation Report')]
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
        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);

        $this->dateFrom = $date->copy()->startOfMonth()->toDateString();
        $this->dateTo = $date->copy()->endOfMonth()->toDateString();
    }

    #[Computed]
    public function organization(): ?Organization
    {
        return Auth::user()?->organization;
    }

    #[Computed]
    public function dateRange(): array
    {
        return [$this->dateFrom, $this->dateTo];
    }

    #[Computed]
    public function availableMonths(): array
    {
        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $date = today()->copy()->subMonths($i);
            $months[$date->format('Y-m')] = $date->format('F Y');
        }

        return $months;
    }

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
            ];
        }

        [$from, $to] = $this->dateRange;

        $donations = Donation::query()
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->whereDate('donations.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('donations.created_at', '<=', $to));

        return [
            'total_gross' => (float) (clone $donations)->sum('gross_amount'),
            'processing_fee' => (float) (clone $donations)->sum('processing_fee') + (float) (clone $donations)->sum('stripe_fee'),
            'net_received' => (float) (clone $donations)->sum('net_amount'),
            'total_donations' => (clone $donations)->count(),
            'unique_donors' => (clone $donations)->distinct('donor_id')->count('donor_id'),
        ];
    }

    #[Computed]
    public function campaignBreakdown()
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
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to)),
            ])
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->whereDate('donations.created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('donations.created_at', '<=', $to))
                    ->selectRaw('SUM(donations.gross_amount)'),
                'gross_amount'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->whereDate('donations.created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('donations.created_at', '<=', $to))
                    ->selectRaw('SUM(donations.processing_fee + donations.stripe_fee)'),
                'processing_fee'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->whereDate('donations.created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('donations.created_at', '<=', $to))
                    ->selectRaw('SUM(donations.net_amount)'),
                'net_amount'
            )
            ->orderByDesc('gross_amount')
            ->get();
    }

    public function render()
    {
        return view('livewire.app.reports.monthly-donations');
    }
}
