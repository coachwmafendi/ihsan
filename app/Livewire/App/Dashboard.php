<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public string $period = '30_days';

    public ?string $customFrom = null;

    public ?string $customTo = null;

    #[Computed]
    public function organization()
    {
        return Auth::user()?->organization;
    }

    public function updatedPeriod(string $value): void
    {
        if ($value === 'custom' && ($this->customFrom === null || $this->customTo === null)) {
            $this->customFrom = now()->subDays(29)->format('Y-m-d');
            $this->customTo = now()->format('Y-m-d');
        }
    }

    #[Computed]
    public function dateRange(): array
    {
        return match ($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            '90_days' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => $this->customDateRange(),
            default => [null, null],
        };
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function customDateRange(): array
    {
        $from = $this->customFrom ? now()->parse($this->customFrom)->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $this->customTo ? now()->parse($this->customTo)->endOfDay() : now()->endOfDay();

        if ($from->isAfter($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    #[Computed]
    public function stats(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        $donationsQuery = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $totalAmount = (float) (clone $donationsQuery)->sum(Donation::reportAmountColumn());
        $totalCount = (clone $donationsQuery)->count();

        return [
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'has_approximation' => Donation::hasReportApproximations(clone $donationsQuery),
            'total_donors' => Donor::whereHas('donations.campaign', fn ($q) => $q->where('organization_id', $org->id))
                ->when($from, fn ($q) => $q->whereHas('donations', fn ($dq) => $dq->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)))
                ->distinct()
                ->count('donors.id'),
            'active_campaigns' => Campaign::where('organization_id', $org->id)
                ->where('status', CampaignStatus::Active->value)
                ->count(),
            'active_subscriptions' => Subscription::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
                ->where('status', SubscriptionStatus::Active->value)
                ->count(),
        ];
    }

    #[Computed]
    public function donationTrend(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        if ($from === null || $to === null) {
            $from = now()->subDays(29)->startOfDay();
            $to = now()->endOfDay();
        }

        $days = max(1, (int) $from->diffInDays($to) + 1);

        $data = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i)->startOfDay();
            $query = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
                ->where('status', DonationStatus::Succeeded)
                ->whereDate('created_at', $date);
            $amount = $query->sum(Donation::reportAmountColumn());
            $data[] = [
                'date' => $date->format('j M'),
                'amount' => (float) $amount,
                'has_approximation' => Donation::hasReportApproximations($query),
            ];
        }

        return $data;
    }

    #[Computed]
    public function campaignsBreakdown(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        return Campaign::where('organization_id', $org->id)
            ->select('campaigns.*')
            ->withCount(['donations' => fn ($q) => $q->where('status', DonationStatus::Succeeded)->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))])
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->whereDate('donations.created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('donations.created_at', '<=', $to))
                    ->select(Donation::reportSumColumn()),
                'report_amount'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->when($from, fn ($q) => $q->whereDate('donations.created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('donations.created_at', '<=', $to))
                    ->where('donations.currency', '!=', 'myr')
                    ->whereNotNull('donations.base_amount')
                    ->selectRaw('COUNT(*) > 0'),
                'has_report_approximation'
            )
            ->orderByDesc('report_amount')
            ->limit(5)
            ->get()
            ->filter(fn ($c) => ($c->report_amount ?? 0) > 0)
            ->map(fn ($c) => [
                'name' => $c->title,
                'amount' => (float) ($c->report_amount ?? 0),
                'has_approximation' => (bool) $c->has_report_approximation,
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function donationSizes(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        $baseQuery = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $reportColumn = Donation::reportAmountSql();

        $under50 = (clone $baseQuery)->whereRaw("{$reportColumn} < ?", [50])->count();
        $fiftyTo100 = (clone $baseQuery)->whereRaw("{$reportColumn} BETWEEN ? AND ?", [50, 100])->count();
        $hundredTo500 = (clone $baseQuery)->whereRaw("{$reportColumn} BETWEEN ? AND ?", [100.01, 500])->count();
        $over500 = (clone $baseQuery)->whereRaw("{$reportColumn} > ?", [500])->count();

        $total = $under50 + $fiftyTo100 + $hundredTo500 + $over500;

        return [
            ['label' => 'Under MYR 50', 'count' => $under50, 'percentage' => $total > 0 ? round(($under50 / $total) * 100) : 0],
            ['label' => 'MYR 50 – 100', 'count' => $fiftyTo100, 'percentage' => $total > 0 ? round(($fiftyTo100 / $total) * 100) : 0],
            ['label' => 'MYR 100 – 500', 'count' => $hundredTo500, 'percentage' => $total > 0 ? round(($hundredTo500 / $total) * 100) : 0],
            ['label' => 'Over MYR 500', 'count' => $over500, 'percentage' => $total > 0 ? round(($over500 / $total) * 100) : 0],
        ];
    }

    #[Computed]
    public function paymentMethods(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        $methods = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->selectRaw('payment_method_type, COUNT(*) as count')
            ->groupBy('payment_method_type')
            ->orderByDesc('count')
            ->get();

        $total = $methods->sum('count');

        return $methods->map(fn ($m) => [
            'name' => match ($m->payment_method_type) {
                'card' => 'Card',
                'bank_transfer' => 'Bank Transfer',
                'fpx' => 'FPX',
                'grabpay' => 'GrabPay',
                'grabpay_paylater' => 'GrabPay PayLater',
                'boost' => 'Boost',
                'tng' => 'Touch n Go',
                'alipay' => 'Alipay',
                'wechatpay' => 'WeChat Pay',
                default => ucfirst($m->payment_method_type ?? 'Other'),
            },
            'count' => (int) $m->count,
            'percentage' => $total > 0 ? round(((int) $m->count / $total) * 100) : 0,
        ])->toArray();
    }

    #[Computed]
    public function recentDonations()
    {
        $org = $this->organization;

        if (! $org) {
            return collect();
        }

        [$from, $to] = $this->dateRange;

        return Donation::with(['campaign', 'donor'])
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->when($from, fn ($q) => $q->whereDate('donations.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('donations.created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function donationSparkline(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        $data = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $amount = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
                ->where('status', DonationStatus::Succeeded)
                ->whereDate('created_at', $date)
                ->sum(Donation::reportAmountColumn());
            $data[] = (int) $amount;
        }

        return $data;
    }

    public function openCreateCampaignModal(): void
    {
        $this->dispatch('open-create-campaign-modal');
    }

    public function render()
    {
        return view('livewire.app.dashboard', [
            'title' => 'Dashboard',
        ]);
    }
}
