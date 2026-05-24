<?php

namespace App\Filament\App\Pages;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class Insights extends Page
{
    protected string $view = 'filament.app.pages.insights';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Insights';

    protected static ?int $navigationSort = 10;

    public string $totalRaised = '0.00';

    public string $monthlyRecurringRevenue = '0.00';

    public int $activeRecurringDonors = 0;

    public string $oneTimeDonationsTotal = '0.00';

    public string $firstInstallmentsTotal = '0.00';

    public string $averageDonationAmount = '0.00';

    public int $successfulDonationsCount = 0;

    public int $totalDonationsCount = 0;

    public int $activeSubscriptionsCount = 0;

    public int $pastDueSubscriptionsCount = 0;

    public string $successRate = '0';

    public string $activeTab = 'overview';

    public array $monthlyRevenue = [];

    public array $campaignPerformance = [];

    public array $subscriptionStatusDistribution = [];

    public array $subscriptionIntervalBreakdown = [];

    public array $mrrOverview = [];

    public array $subscriptionTrend = [];

    public array $retentionOverview = [];

    public array $paymentBrandBreakdown = [];

    public array $paymentTypeBreakdown = [];

    public array $elementsList = [];

    public array $campaignUrlPerformance = [];

    /**
     * @var array<int, array{label: string, amount: string, height: int}>
     */
    public array $dailyRevenue = [];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $frequencyBreakdown = [];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    public array $statusBreakdown = [];

    /**
     * @var array<int, array{donor: string, campaign: string, amount: string, type: string}>
     */
    public array $recentDonations = [];

    public string $dateRange = '7_days';

    public ?int $selectedCampaignId = null;

    public string $selectedCampaignLabel = 'All';

    public string $frequencyFilter = 'all';

    public string $aggregation = 'daily';

    public bool $hasCampaigns = false;

    public array $filterCampaigns = [];

    public function mount(): void
    {
        $this->filterCampaigns = Campaign::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->pluck('title', 'id')
            ->all();

        $this->hasCampaigns = count($this->filterCampaigns) > 0;

        $this->loadData();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function setDateRange(string $range): void
    {
        $this->dateRange = $range;
        $this->loadData();
    }

    public function cycleDateRange(): void
    {
        $options = ['7_days', '30_days', '90_days', 'this_month', 'all'];
        $index = array_search($this->dateRange, $options);
        $this->dateRange = $options[($index + 1) % count($options)];
        $this->loadData();
    }

    public function cycleCampaign(): void
    {
        $campaignIds = array_keys($this->filterCampaigns);

        if (empty($campaignIds)) {
            return;
        }

        if ($this->selectedCampaignId === null) {
            $this->selectedCampaignId = $campaignIds[0];
        } else {
            $index = array_search($this->selectedCampaignId, $campaignIds);

            if ($index === false || $index === count($campaignIds) - 1) {
                $this->selectedCampaignId = null;
            } else {
                $this->selectedCampaignId = $campaignIds[$index + 1];
            }
        }

        $this->selectedCampaignLabel = $this->selectedCampaignId !== null
            ? ($this->filterCampaigns[$this->selectedCampaignId] ?? 'All')
            : 'All';

        $this->loadData();
    }

    public function cycleFrequency(): void
    {
        $options = ['all', 'one_time', 'recurring'];
        $index = array_search($this->frequencyFilter, $options);
        $this->frequencyFilter = $options[($index + 1) % count($options)];
        $this->loadData();
    }

    private function getDateStart()
    {
        return match ($this->dateRange) {
            '7_days' => Carbon::now()->subDays(7)->startOfDay(),
            '30_days' => Carbon::now()->subDays(30)->startOfDay(),
            '90_days' => Carbon::now()->subDays(90)->startOfDay(),
            'this_month' => Carbon::now()->startOfMonth(),
            default => null,
        };
    }

    private function loadData(): void
    {
        $campaignIdsQuery = Campaign::query()
            ->where('organization_id', auth()->user()->organization_id);

        if ($this->selectedCampaignId !== null) {
            $campaignIdsQuery->where('id', $this->selectedCampaignId);
        }

        $campaignIds = $campaignIdsQuery->pluck('id');
        $dateStart = $this->getDateStart();

        $successfulDonations = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded);

        $this->applyDateFilter($successfulDonations, $dateStart);

        if ($this->frequencyFilter !== 'all') {
            $successfulDonations->where('type', $this->frequencyFilter === 'one_time' ? DonationType::OneTime : DonationType::Recurring);
        }

        $this->totalRaised = $this->formatMoney((float) (clone $successfulDonations)->sum('gross_amount'));

        $this->monthlyRecurringRevenue = number_format((float) Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::Active)
            ->where('interval', 'monthly')
            ->sum('amount'), 2, '.', '');

        $this->activeRecurringDonors = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::Active)
            ->distinct('donor_id')
            ->count('donor_id');

        $this->oneTimeDonationsTotal = $this->formatMoney((float) (clone $successfulDonations)
            ->where('type', DonationType::OneTime)
            ->sum('gross_amount'));

        $this->firstInstallmentsTotal = $this->formatMoney((float) (clone $successfulDonations)
            ->where('type', DonationType::Recurring)
            ->sum('gross_amount'));

        $this->successfulDonationsCount = (clone $successfulDonations)->count();

        $totalDonationsQuery = Donation::query()
            ->whereIn('campaign_id', $campaignIds);

        $this->applyDateFilter($totalDonationsQuery, $dateStart);

        if ($this->frequencyFilter !== 'all') {
            $totalDonationsQuery->where('type', $this->frequencyFilter === 'one_time' ? DonationType::OneTime : DonationType::Recurring);
        }

        $this->totalDonationsCount = $totalDonationsQuery->count();

        $this->averageDonationAmount = $this->formatMoney($this->successfulDonationsCount === 0
            ? 0
            : (float) (clone $successfulDonations)->sum('gross_amount') / $this->successfulDonationsCount);

        $this->activeSubscriptionsCount = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $this->pastDueSubscriptionsCount = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::PastDue)
            ->count();

        $this->successRate = (string) ($this->totalDonationsCount === 0
            ? 0
            : round(($this->successfulDonationsCount / $this->totalDonationsCount) * 100));

        $this->dailyRevenue = $this->buildDailyRevenue((clone $successfulDonations)->get());
        $this->frequencyBreakdown = $this->buildFrequencyBreakdown($campaignIds->all(), $dateStart);
        $this->statusBreakdown = $this->buildStatusBreakdown($campaignIds->all(), $dateStart);
        $this->recentDonations = $this->buildRecentDonations($campaignIds->all(), $dateStart);

        $this->monthlyRevenue = $this->buildMonthlyRevenue($campaignIds->all());
        $this->campaignPerformance = $this->buildCampaignPerformance($campaignIds->all(), $dateStart);
        $this->subscriptionStatusDistribution = $this->buildSubscriptionStatusDistribution($campaignIds->all());
        $this->subscriptionIntervalBreakdown = $this->buildSubscriptionIntervalBreakdown($campaignIds->all());
        $this->mrrOverview = $this->buildMrrOverview($campaignIds->all());
        $this->subscriptionTrend = $this->buildSubscriptionTrend($campaignIds->all());
        $this->retentionOverview = $this->buildRetentionOverview($campaignIds->all(), $dateStart);
        $this->paymentBrandBreakdown = $this->buildPaymentBrandBreakdown($campaignIds->all(), $dateStart);
        $this->paymentTypeBreakdown = $this->buildPaymentTypeBreakdown($campaignIds->all(), $dateStart);
        $this->elementsList = $this->buildElementsList();
        $this->campaignUrlPerformance = $this->buildCampaignUrlPerformance($campaignIds->all(), $dateStart);
    }

    private function applyDateFilter($query, $dateStart): void
    {
        if ($dateStart !== null) {
            $query->where('created_at', '>=', $dateStart);
        }
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @param  Collection<int, Donation>  $donations
     * @return array<int, array{label: string, amount: string, height: int}>
     */
    private function buildDailyRevenue(Collection $donations): array
    {
        $days = collect(range(6, 0))
            ->map(fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString());

        $amounts = $days->mapWithKeys(fn (string $date) => [
            $date => (float) $donations
                ->filter(fn (Donation $donation) => $donation->created_at->toDateString() === $date)
                ->sum('gross_amount'),
        ]);

        $max = max($amounts->max(), 1);

        return $amounts
            ->map(fn (float $amount, string $date): array => [
                'label' => now()->parse($date)->format('M j'),
                'amount' => $this->formatMoney($amount),
                'height' => max(8, (int) round(($amount / $max) * 100)),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{label: string, value: string}>
     */
    private function buildFrequencyBreakdown(array $campaignIds, $dateStart = null): array
    {
        $oneTimeQuery = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->where('type', DonationType::OneTime);

        $recurringQuery = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->where('type', DonationType::Recurring);

        $this->applyDateFilter($oneTimeQuery, $dateStart);
        $this->applyDateFilter($recurringQuery, $dateStart);

        return [
            ['label' => 'One-time', 'value' => 'MYR '.$this->formatMoney((float) $oneTimeQuery->sum('gross_amount'))],
            ['label' => 'Recurring', 'value' => 'MYR '.$this->formatMoney((float) $recurringQuery->sum('gross_amount'))],
        ];
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{label: string, value: string}>
     */
    private function buildStatusBreakdown(array $campaignIds, $dateStart = null): array
    {
        return collect(DonationStatus::cases())
            ->map(fn (DonationStatus $status): array => [
                'label' => str($status->value)->headline()->toString(),
                'value' => (string) Donation::query()
                    ->whereIn('campaign_id', $campaignIds)
                    ->where('status', $status)
                    ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
                    ->count(),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{donor: string, campaign: string, amount: string, type: string}>
     */
    private function buildRecentDonations(array $campaignIds, $dateStart = null): array
    {
        return Donation::query()
            ->with(['campaign:id,title', 'donor:id,name'])
            ->whereIn('campaign_id', $campaignIds)
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Donation $donation): array => [
                'donor' => $donation->donor->name,
                'campaign' => $donation->campaign->title,
                'amount' => 'MYR '.$this->formatMoney((float) $donation->gross_amount),
                'type' => str($donation->type->value)->headline()->toString(),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{month: string, amount: string, successRate: string, averageAmount: string}>
     */
    private function buildMonthlyRevenue(array $campaignIds): array
    {
        $donations = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->get(['created_at', 'gross_amount', 'status']);

        $months = collect(range(11, 0))
            ->map(fn (int $i) => now()->subMonthsNoOverflow($i)->format('Y-m'));

        $grouped = $donations->groupBy(fn (Donation $d) => $d->created_at->format('Y-m'));

        return $months
            ->reverse()
            ->values()
            ->map(function (string $month) use ($grouped) {
                $monthDonations = $grouped->get($month, collect());
                $total = (float) $monthDonations->where('status', DonationStatus::Succeeded)->sum('gross_amount');
                $totalCount = $monthDonations->count();
                $successCount = $monthDonations->where('status', DonationStatus::Succeeded)->count();

                return [
                    'month' => now()->parse($month.'-01')->format('M Y'),
                    'amount' => $this->formatMoney($total),
                    'successRate' => $totalCount > 0
                        ? round(($successCount / $totalCount) * 100).'%'
                        : '0%',
                    'averageAmount' => $this->formatMoney(
                        $successCount > 0 ? $total / $successCount : 0
                    ),
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{campaign: string, total: string, donationCount: int, successRate: string}>
     */
    private function buildCampaignPerformance(array $campaignIds, $dateStart = null): array
    {
        $campaigns = Campaign::whereIn('id', $campaignIds)->get()->keyBy('id');

        return Donation::query()
            ->selectRaw('campaign_id')
            ->selectRaw('SUM(CASE WHEN status = ? THEN gross_amount ELSE 0 END) as total', [DonationStatus::Succeeded->value])
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success_count', [DonationStatus::Succeeded->value])
            ->whereIn('campaign_id', $campaignIds)
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->groupBy('campaign_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'campaign' => $campaigns[$row->campaign_id]?->title ?? 'Unknown',
                'total' => 'MYR '.$this->formatMoney((float) $row->total),
                'donationCount' => (int) $row->success_count,
                'successRate' => $row->total_count > 0
                    ? round(($row->success_count / $row->total_count) * 100).'%'
                    : '0%',
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{status: string, count: int, label: string}>
     */
    private function buildSubscriptionStatusDistribution(array $campaignIds): array
    {
        $counts = Subscription::query()
            ->selectRaw('status, COUNT(*) as count')
            ->whereIn('campaign_id', $campaignIds)
            ->groupBy('status')
            ->pluck('count', 'status');

        return collect(SubscriptionStatus::cases())
            ->map(fn (SubscriptionStatus $status) => [
                'status' => $status->value,
                'count' => (int) ($counts[$status->value] ?? 0),
                'label' => str($status->value)->headline()->toString(),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{interval: string, count: int, total: string}>
     */
    private function buildSubscriptionIntervalBreakdown(array $campaignIds): array
    {
        $aggregated = Subscription::query()
            ->selectRaw('interval, COUNT(*) as count, SUM(amount) as total')
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::Active)
            ->groupBy('interval')
            ->get()
            ->keyBy('interval');

        return collect(SubscriptionInterval::cases())
            ->map(fn (SubscriptionInterval $interval) => [
                'interval' => str($interval->value)->headline()->toString(),
                'count' => (int) ($aggregated[$interval->value]?->count ?? 0),
                'total' => 'MYR '.$this->formatMoney((float) ($aggregated[$interval->value]?->total ?? 0)),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array{currentMrr: string, activeCount: int, newThisMonth: int, cancelledThisMonth: int}
     */
    private function buildMrrOverview(array $campaignIds): array
    {
        $activeSubs = Subscription::query()->whereIn('campaign_id', $campaignIds)->where('status', SubscriptionStatus::Active);

        $mrr = (float) (clone $activeSubs)->where('interval', 'monthly')->sum('amount');

        $newThisMonth = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::PastDue, SubscriptionStatus::Paused])
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $cancelledThisMonth = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', SubscriptionStatus::Cancelled)
            ->where('cancelled_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'currentMrr' => 'MYR '.$this->formatMoney($mrr),
            'activeCount' => (clone $activeSubs)->count(),
            'newThisMonth' => $newThisMonth,
            'cancelledThisMonth' => $cancelledThisMonth,
        ];
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{month: string, newSubs: int, cancelledSubs: int, totalActive: int}>
     */
    private function buildSubscriptionTrend(array $campaignIds): array
    {
        $subscriptions = Subscription::query()
            ->whereIn('campaign_id', $campaignIds)
            ->get(['created_at', 'cancelled_at', 'status']);

        $totalActive = $subscriptions->where('status', SubscriptionStatus::Active)->count();

        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonthsNoOverflow($i)->startOfMonth())
            ->reverse()
            ->values()
            ->map(function ($monthStart) use ($subscriptions, $totalActive) {
                $monthEnd = (clone $monthStart)->endOfMonth();

                return [
                    'month' => $monthStart->format('M Y'),
                    'newSubs' => $subscriptions
                        ->filter(fn (Subscription $s) => $s->created_at->between($monthStart, $monthEnd))
                        ->count(),
                    'cancelledSubs' => $subscriptions
                        ->filter(fn (Subscription $s) => $s->cancelled_at !== null && $s->cancelled_at->between($monthStart, $monthEnd))
                        ->count(),
                    'totalActive' => $totalActive,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array{totalDonors: int, repeatDonors: int, repeatRate: string, newThisMonth: int, returningThisMonth: int}
     */
    private function buildRetentionOverview(array $campaignIds, $dateStart = null): array
    {
        $donorDonationCounts = Donation::query()
            ->selectRaw('donor_id')
            ->selectRaw('COUNT(*) as donation_count')
            ->selectRaw('MIN(created_at) as first_donation')
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->groupBy('donor_id')
            ->get();

        $totalDonors = $donorDonationCounts->count();
        $repeatDonors = $donorDonationCounts->filter(fn ($d) => $d->donation_count > 1)->count();

        $newThisMonth = $donorDonationCounts
            ->filter(fn ($d) => $d->first_donation >= now()->startOfMonth())
            ->count();

        $returningThisMonth = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->where('created_at', '>=', now()->startOfMonth())
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->whereIn('donor_id', function ($q) use ($campaignIds) {
                $q->select('donor_id')
                    ->from('donations')
                    ->whereIn('campaign_id', $campaignIds)
                    ->where('status', DonationStatus::Succeeded)
                    ->where('created_at', '<', now()->startOfMonth())
                    ->groupBy('donor_id');
            })
            ->distinct('donor_id')
            ->count('donor_id');

        return [
            'totalDonors' => $totalDonors,
            'repeatDonors' => $repeatDonors,
            'repeatRate' => $totalDonors > 0 ? round(($repeatDonors / $totalDonors) * 100).'%' : '0%',
            'newThisMonth' => $newThisMonth,
            'returningThisMonth' => $returningThisMonth,
        ];
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{brand: string, count: int, percentage: float}>
     */
    private function buildPaymentBrandBreakdown(array $campaignIds, $dateStart = null): array
    {
        $total = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->whereNotNull('payment_method_brand')
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->count();

        $rows = Donation::query()
            ->selectRaw('payment_method_brand')
            ->selectRaw('COUNT(*) as count')
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->whereNotNull('payment_method_brand')
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->groupBy('payment_method_brand')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'brand' => str($row->payment_method_brand)->headline()->toString(),
                'count' => (int) $row->count,
                'percentage' => $total > 0 ? round(((int) $row->count / $total) * 100, 1) : 0,
            ])
            ->all();

        return $rows;
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{type: string, count: int, total: string, percentage: float}>
     */
    private function buildPaymentTypeBreakdown(array $campaignIds, $dateStart = null): array
    {
        $total = Donation::query()
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->whereNotNull('payment_method_type')
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->count();

        return Donation::query()
            ->selectRaw('payment_method_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(gross_amount) as total_amount')
            ->whereIn('campaign_id', $campaignIds)
            ->where('status', DonationStatus::Succeeded)
            ->whereNotNull('payment_method_type')
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->groupBy('payment_method_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'type' => str($row->payment_method_type)->headline()->toString(),
                'count' => (int) $row->count,
                'total' => 'MYR '.$this->formatMoney((float) $row->total_amount),
                'percentage' => $total > 0 ? round(((int) $row->count / $total) * 100, 1) : 0,
            ])
            ->all();
    }

    /**
     * @return array<int, array{name: string, type: string, campaign: string, isActive: bool}>
     */
    private function buildElementsList(): array
    {
        return Element::query()
            ->with('campaign:id,title')
            ->where('organization_id', auth()->user()->organization_id)
            ->get()
            ->map(fn (Element $element) => [
                'name' => $element->name,
                'type' => str($element->type->value)->headline()->toString(),
                'campaign' => $element->campaign?->title ?? '-',
                'isActive' => $element->is_active,
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $campaignIds
     * @return array<int, array{campaign: string, totalDonations: int, totalAmount: string, donationCount: int}>
     */
    private function buildCampaignUrlPerformance(array $campaignIds, $dateStart = null): array
    {
        $campaigns = Campaign::whereIn('id', $campaignIds)->get()->keyBy('id');

        return Donation::query()
            ->selectRaw('campaign_id')
            ->selectRaw('COUNT(*) as total_donations')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as successful_count', [DonationStatus::Succeeded->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN gross_amount ELSE 0 END) as total_amount', [DonationStatus::Succeeded->value])
            ->whereIn('campaign_id', $campaignIds)
            ->when($dateStart !== null, fn ($q) => $q->where('created_at', '>=', $dateStart))
            ->groupBy('campaign_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'campaign' => $campaigns[$row->campaign_id]?->title ?? 'Unknown',
                'totalDonations' => (int) $row->total_donations,
                'totalAmount' => 'MYR '.$this->formatMoney((float) $row->total_amount),
                'donationCount' => (int) $row->successful_count,
            ])
            ->all();
    }
}
