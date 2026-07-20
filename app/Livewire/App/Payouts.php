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
    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $statusFilter = '';

    public function mount(): void
    {
        $this->dateFrom = today()->subMonths(11)->format('Y-m-d');
        $this->dateTo = today()->format('Y-m-d');
    }

    #[Computed]
    public function organization()
    {
        return Auth::user()?->organization;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(): array
    {
        $from = filled($this->dateFrom)
            ? CarbonImmutable::parse($this->dateFrom)->startOfDay()
            : today()->subMonths(11)->startOfDay();

        $to = filled($this->dateTo)
            ? CarbonImmutable::parse($this->dateTo)->endOfDay()
            : today()->endOfDay();

        if ($from->gt($to)) {
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
            ->whereDate('arrival_date', '>=', $from)
            ->whereDate('arrival_date', '<=', $to)
            ->when(filled($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter));
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

    public function render()
    {
        return view('livewire.app.payouts');
    }
}
