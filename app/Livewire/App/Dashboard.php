<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $period = 'today';

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
    public function recurringHealth(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [
                'mrr' => 0.0,
                'mrr_has_approximation' => false,
                'at_risk_count' => 0,
                'expected_30_days' => 0.0,
                'expected_30_days_has_approximation' => false,
            ];
        }

        $monthlyMultiplier = [
            SubscriptionInterval::Weekly->value => 52 / 12,
            SubscriptionInterval::Biweekly->value => 26 / 12,
            SubscriptionInterval::Monthly->value => 1,
            SubscriptionInterval::Bimonthly->value => 6 / 12,
            SubscriptionInterval::Quarterly->value => 4 / 12,
            SubscriptionInterval::Semiannual->value => 2 / 12,
            SubscriptionInterval::Yearly->value => 1 / 12,
        ];

        $activeSubscriptions = Subscription::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', SubscriptionStatus::Active->value)
            ->get(['amount', 'currency', 'interval', 'next_charge_at']);

        $mrr = 0.0;
        $mrrHasApproximation = false;

        foreach ($activeSubscriptions as $subscription) {
            $multiplier = $monthlyMultiplier[$subscription->interval->value] ?? 1;
            $mrr += (float) $subscription->amount * $multiplier;

            if (strtolower($subscription->currency) !== 'myr') {
                $mrrHasApproximation = true;
            }
        }

        $expected30Days = 0.0;
        $expected30DaysHasApproximation = false;
        $windowEnd = now()->addDays(30);

        foreach ($activeSubscriptions as $subscription) {
            if ($subscription->next_charge_at === null) {
                continue;
            }

            if ($subscription->next_charge_at->between(now(), $windowEnd)) {
                $expected30Days += (float) $subscription->amount;

                if (strtolower($subscription->currency) !== 'myr') {
                    $expected30DaysHasApproximation = true;
                }
            }
        }

        $atRiskCount = Subscription::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->whereIn('status', [SubscriptionStatus::PastDue->value, SubscriptionStatus::Failed->value])
            ->count();

        return [
            'mrr' => $mrr,
            'mrr_has_approximation' => $mrrHasApproximation,
            'at_risk_count' => $atRiskCount,
            'expected_30_days' => $expected30Days,
            'expected_30_days_has_approximation' => $expected30DaysHasApproximation,
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

    /**
     * @return array{
     *     days: array<int, array{date: string, date_from_key: string, date_to_key: string, label: string, one_time: int, recurring: int, total: int}>,
     *     one_time_total: int,
     *     recurring_total: int,
     *     max_scale: int,
     *     step: int,
     *     donations_url: string,
     * }
     */
    #[Computed]
    public function donationsByFrequency(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [
                'days' => [],
                'one_time_total' => 0,
                'recurring_total' => 0,
                'max_scale' => 10,
                'step' => 2,
                'donations_url' => route('app.donations.index'),
            ];
        }

        [$from, $to] = $this->dateRange;

        if ($from === null || $to === null) {
            $from = now()->subDays(6)->startOfDay();
            $to = now()->endOfDay();
        }

        $counts = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as donation_date, type, COUNT(*) as count')
            ->groupByRaw('DATE(created_at), type')
            ->get()
            ->groupBy('donation_date');

        $data = [];
        $oneTimeTotal = 0;
        $recurringTotal = 0;
        $maxBucket = 0;

        foreach ($this->frequencyBuckets($from, $to) as [$bucketStart, $bucketEnd, $dateLabel, $tooltipLabel]) {
            $oneTime = 0;
            $recurring = 0;

            for ($date = $bucketStart; $date->lte($bucketEnd); $date = $date->addDay()) {
                $dayRows = $counts->get($date->format('Y-m-d'), collect());
                $oneTime += (int) ($dayRows->firstWhere('type', DonationType::OneTime)?->count ?? 0);
                $recurring += (int) ($dayRows->firstWhere('type', DonationType::Recurring)?->count ?? 0);
            }

            $total = $oneTime + $recurring;
            $oneTimeTotal += $oneTime;
            $recurringTotal += $recurring;
            $maxBucket = max($maxBucket, $total);

            $data[] = [
                'date' => $dateLabel,
                'date_from_key' => $bucketStart->format('Y-m-d'),
                'date_to_key' => $bucketEnd->format('Y-m-d'),
                'label' => $tooltipLabel,
                'one_time' => $oneTime,
                'recurring' => $recurring,
                'total' => $total,
            ];
        }

        $maxScale = $this->niceMaxForFrequency($maxBucket);
        $step = (int) ($maxScale / 5);

        return [
            'days' => $data,
            'one_time_total' => $oneTimeTotal,
            'recurring_total' => $recurringTotal,
            'max_scale' => $maxScale,
            'step' => $step,
            'donations_url' => route('app.donations.index'),
        ];
    }

    /**
     * Bucket the range daily (≤ 31 days), weekly (≤ 182 days), or monthly so long
     * periods like 90D render a readable number of bars.
     *
     * @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable, 2: string, 3: string}>
     */
    private function frequencyBuckets(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = $from->toImmutable()->startOfDay();
        $to = $to->toImmutable();

        $days = max(1, (int) $from->diffInDays($to) + 1);
        $buckets = [];

        if ($days <= 31) {
            for ($i = 0; $i < $days; $i++) {
                $date = $from->addDays($i);
                $buckets[] = [$date, $date, $date->format('M d'), $date->format('j M')];
            }

            return $buckets;
        }

        if ($days <= 182) {
            for ($start = $from; $start->lte($to); $start = $start->addDays(7)) {
                $end = $start->addDays(6)->min($to)->startOfDay();
                $buckets[] = [
                    $start,
                    $end,
                    $start->format('j M'),
                    $start->format('j M').' – '.$end->format('j M'),
                ];
            }

            return $buckets;
        }

        for ($start = $from; $start->lte($to); $start = $start->startOfMonth()->addMonth()) {
            $end = $start->endOfMonth()->min($to)->startOfDay();
            $buckets[] = [$start, $end, $start->format('M Y'), $start->format('M Y')];
        }

        return $buckets;
    }

    private function niceMaxForFrequency(int $max): int
    {
        if ($max <= 10) {
            return 10;
        }

        if ($max <= 25) {
            return 25;
        }

        if ($max <= 50) {
            return 50;
        }

        if ($max <= 100) {
            return 100;
        }

        return (int) ceil($max / 50) * 50;
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
            ->selectRaw('payment_method_type, COUNT(*) as count, SUM('.Donation::reportAmountSql().') as total_amount')
            ->groupBy('payment_method_type')
            ->orderByDesc('total_amount')
            ->get();

        $total = (float) $methods->sum('total_amount');

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
            'value' => (float) $m->total_amount,
            'label' => 'MYR '.number_format((float) $m->total_amount, 2),
            'percentage' => $total > 0 ? round(((float) $m->total_amount / $total) * 100) : 0,
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
        return view('livewire.app.dashboard');
    }
}
