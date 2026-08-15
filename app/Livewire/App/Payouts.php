<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Models\Payout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Payouts')]
class Payouts extends Component
{
    public string $dateOperator = 'last';

    public ?int $dateValue = 11;

    public string $dateUnit = 'months';

    public ?string $dateSingle = null;

    public ?string $pendingDateFrom = null;

    public ?string $pendingDateTo = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $amountOperator = 'equal';

    public ?float $amountValue = null;

    public ?float $amountMin = null;

    public ?float $amountMax = null;

    public ?float $amountFrom = null;

    public ?float $amountTo = null;

    public ?string $statusFilter = '';

    public function mount(): void
    {
        $this->applyDateFilter();
    }

    #[Computed]
    public function organization()
    {
        return Auth::user()?->organization;
    }

    public function applyDateFilter(): void
    {
        switch ($this->dateOperator) {
            case 'last':
                $this->dateFrom = now()->sub((int) $this->dateValue, $this->dateUnit)->startOfDay()->format('Y-m-d');
                $this->dateTo = today()->format('Y-m-d');
                break;

            case 'equal':
                $date = $this->dateSingle ?? today()->format('Y-m-d');
                $this->dateFrom = $date;
                $this->dateTo = $date;
                break;

            case 'between':
                $this->dateFrom = $this->pendingDateFrom ?? today()->subMonth()->format('Y-m-d');
                $this->dateTo = $this->pendingDateTo ?? today()->format('Y-m-d');

                if ($this->dateFrom && $this->dateTo && $this->dateFrom > $this->dateTo) {
                    [$this->dateFrom, $this->dateTo] = [$this->dateTo, $this->dateFrom];
                }
                break;

            case 'on_or_after':
                $this->dateFrom = $this->dateSingle ?? today()->format('Y-m-d');
                $this->dateTo = null;
                break;

            case 'before_or_on':
                $this->dateFrom = null;
                $this->dateTo = $this->dateSingle ?? today()->format('Y-m-d');
                break;
        }
    }

    public function applyAmountFilter(): void
    {
        switch ($this->amountOperator) {
            case 'between':
                $this->amountFrom = $this->amountMin;
                $this->amountTo = $this->amountMax;
                break;

            case 'greater_than':
                $this->amountFrom = $this->amountValue;
                $this->amountTo = null;
                break;

            case 'less_than':
                $this->amountFrom = null;
                $this->amountTo = $this->amountValue;
                break;

            default:
                $this->amountFrom = $this->amountValue;
                $this->amountTo = $this->amountValue;
                break;
        }
    }

    public function applyStatusFilter(): void
    {
        // statusFilter is bound directly; this method lets the UI trigger a refresh.
    }

    public function clearDateFilter(): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
    }

    public function clearAmountFilter(): void
    {
        $this->amountFrom = null;
        $this->amountTo = null;
    }

    public function clearStatusFilter(): void
    {
        $this->statusFilter = '';
    }

    public function clearFilters(): void
    {
        $this->clearDateFilter();
        $this->clearAmountFilter();
        $this->clearStatusFilter();
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    private function dateRange(): array
    {
        $from = filled($this->dateFrom)
            ? CarbonImmutable::parse($this->dateFrom)->startOfDay()
            : null;

        $to = filled($this->dateTo)
            ? CarbonImmutable::parse($this->dateTo)->endOfDay()
            : null;

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    private function baseQuery()
    {
        $org = $this->organization;

        if (! $org) {
            return Payout::query()->whereNull('organization_id');
        }

        [$from, $to] = $this->dateRange();

        return Payout::query()
            ->where('organization_id', $org->id)
            ->when($from, fn ($q) => $q->whereDate('arrival_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('arrival_date', '<=', $to))
            ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
            ->when(! is_null($this->amountFrom), fn ($q) => $q->where('amount', '>=', (int) round($this->amountFrom * 100)))
            ->when(! is_null($this->amountTo), fn ($q) => $q->where('amount', '<=', (int) round($this->amountTo * 100)));
    }

    #[Computed]
    public function payouts()
    {
        return $this->baseQuery()
            ->orderByDesc('arrival_date')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function summary(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [
                'paid_this_month' => 0.0,
                'pending' => 0.0,
                'next_expected' => 0.0,
                'next_expected_at' => null,
            ];
        }

        $now = now();

        $paidThisMonth = Payout::query()
            ->where('organization_id', $org->id)
            ->where('status', 'paid')
            ->whereBetween('arrival_date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->sum('amount');

        $pending = Payout::query()
            ->where('organization_id', $org->id)
            ->whereIn('status', ['pending', 'in_transit'])
            ->sum('amount');

        $nextExpected = Payout::query()
            ->where('organization_id', $org->id)
            ->whereIn('status', ['pending', 'in_transit'])
            ->orderBy('arrival_date')
            ->first();

        return [
            'paid_this_month' => (float) ($paidThisMonth / 100),
            'pending' => (float) ($pending / 100),
            'next_expected' => $nextExpected?->amount ? (float) ($nextExpected->amount / 100) : 0.0,
            'next_expected_at' => $nextExpected?->arrival_date?->format('j M Y'),
        ];
    }

    public function dateChipLabel(): string
    {
        return match ($this->dateOperator) {
            'last' => "In the last {$this->dateValue} {$this->dateUnit}",
            'equal' => 'On '.$this->formatDate($this->dateFrom),
            'between' => 'From '.$this->formatDate($this->dateFrom).' to '.$this->formatDate($this->dateTo),
            'on_or_after' => 'On or after '.$this->formatDate($this->dateFrom),
            'before_or_on' => 'On or before '.$this->formatDate($this->dateTo),
            default => 'Date',
        };
    }

    public function amountChipLabel(): string
    {
        return match ($this->amountOperator) {
            'between' => 'MYR '.number_format((float) $this->amountMin, 2).' - '.number_format((float) $this->amountMax, 2),
            'greater_than' => '> MYR '.number_format((float) $this->amountValue, 2),
            'less_than' => '< MYR '.number_format((float) $this->amountValue, 2),
            default => '= MYR '.number_format((float) $this->amountValue, 2),
        };
    }

    public function statusChipLabel(): string
    {
        return $this->statusFilter ? ucfirst(str_replace('_', ' ', $this->statusFilter)) : 'Status';
    }

    public function hasDateFilter(): bool
    {
        return filled($this->dateFrom) || filled($this->dateTo);
    }

    public function hasAmountFilter(): bool
    {
        return ! is_null($this->amountFrom) || ! is_null($this->amountTo);
    }

    public function hasStatusFilter(): bool
    {
        return filled($this->statusFilter);
    }

    public function hasAnyFilter(): bool
    {
        return $this->hasDateFilter() || $this->hasAmountFilter() || $this->hasStatusFilter();
    }

    private function formatDate(?string $date): string
    {
        return $date ? CarbonImmutable::parse($date)->format('d/m/Y') : '—';
    }

    public function render()
    {
        return view('livewire.app.payouts');
    }
}
