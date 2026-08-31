<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Setting;
use App\Support\ReportingPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Billing')]
class Billing extends Component
{
    public string $selectedMonth = '';

    public array $availableMonths = [];

    public float $amountProcessed = 0;

    public float $donorFeeCovered = 0;

    public float $stripeFee = 0;

    public float $processingFee = 0;

    public float $netReceived = 0;

    public int $totalTransactions = 0;

    public string $processingFeeRate = '2.5';

    public function mount(): void
    {
        $this->processingFeeRate = number_format((float) Setting::get('payment_processing_fee_percent', 2.5), 1);
        $this->buildMonthOptions();
        $this->selectedMonth = today()->format('Y-m');
        $this->calculate();
    }

    public function updatedSelectedMonth(): void
    {
        $this->calculate();
    }

    public function calculate(): void
    {
        $org = Auth::user()?->organization;
        if (! $org) {
            return;
        }

        [$from, $to] = $this->dateRange();

        $donations = Donation::query()
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->where('donations.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('donations.created_at', '<=', $to));

        $this->totalTransactions = (clone $donations)->count();
        $this->amountProcessed = (float) (clone $donations)->sum('base_amount');
        $this->donorFeeCovered = (float) (clone $donations)->sum('donor_fee_covered');
        $this->stripeFee = (float) (clone $donations)->sum('stripe_fee');
        $this->processingFee = (float) (clone $donations)->sum('processing_fee');
        $this->netReceived = (float) (clone $donations)->sum('net_amount');
    }

    private function dateRange(): array
    {
        if (blank($this->selectedMonth)) {
            return [null, null];
        }
        // The month is a Malaysian one; the query compares UTC instants.
        $date = $this->reportingPeriod()->parseLocalDate($this->selectedMonth.'-01');

        return $this->reportingPeriod()->toUtc([
            $date->startOfMonth(),
            $date->endOfMonth(),
        ]);
    }

    private function buildMonthOptions(): void
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date = $this->reportingPeriod()->localNow()->subMonths($i);
            $months[$date->format('Y-m')] = $date->format('F Y');
        }
        $this->availableMonths = $months;
    }

    public function render()
    {
        return view('livewire.app.billing');
    }

    /**
     * Days are measured on this organization's clock; see ReportingPeriod.
     */
    private function reportingPeriod(): ReportingPeriod
    {
        return ReportingPeriod::for(Auth::user()?->organization);
    }
}
