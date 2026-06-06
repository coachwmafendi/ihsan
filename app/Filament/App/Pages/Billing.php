<?php

namespace App\Filament\App\Pages;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Setting;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class Billing extends Page
{
    protected string $view = 'filament.app.pages.billing';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Billing';

    protected static ?string $title = 'Billing';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    public string $selectedMonth = '';

    public array $availableMonths = [];

    public string $amountProcessed = '0.00';

    public string $processingFee = '0.00';

    public string $stripeFee = '0.00';

    public string $netReceived = '0.00';

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
        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        [$from, $to] = $this->dateRange();

        $donations = Donation::query()
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn (Builder $q) => $q->whereDate('donations.created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('donations.created_at', '<=', $to));

        $this->totalTransactions = (clone $donations)->count();
        $this->amountProcessed = number_format((float) (clone $donations)->sum('gross_amount'), 2, '.', '');
        $this->stripeFee = number_format((float) (clone $donations)->sum('stripe_fee'), 2, '.', '');
        $this->processingFee = number_format((float) (clone $donations)->sum('processing_fee'), 2, '.', '');
        $this->netReceived = number_format((float) (clone $donations)->sum('net_amount'), 2, '.', '');
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function dateRange(): array
    {
        if (blank($this->selectedMonth)) {
            return [null, null];
        }

        $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);

        return [
            $date->copy()->startOfMonth()->toDateString(),
            $date->copy()->endOfMonth()->toDateString(),
        ];
    }

    private function buildMonthOptions(): void
    {
        $months = [];
        $now = today();

        for ($i = 0; $i < 12; $i++) {
            $date = $now->copy()->subMonths($i);
            $months[$date->format('Y-m')] = $date->format('F Y');
        }

        $this->availableMonths = $months;
    }
}
