# Insights Page Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the Insights page from a single-scroll layout to a tabbed analytics dashboard with 9 sections (all except UTM).

**Architecture:** Single Livewire Filament page (`Insights.php`) with `$activeTab` state. All data computed once in `mount()`, stored in per-section array properties. Blade view uses `@switch` on `$activeTab` to render the correct section content. Sidebar becomes interactive tab navigation.

**Tech Stack:** Filament v5, Livewire v4, Laravel v13, Blade, Tailwind v4

---

## File Structure

**Modify:**
- `app/Filament/App/Pages/Insights.php` — add activeTab state, per-tab data arrays, builder methods
- `resources/views/filament/app/pages/insights.blade.php` — tabbed navigation layout, per-section content
- `tests/Feature/Ihsan/InsightsPageTest.php` — add assertions for new data and tab switching

---

### Task 1: Add activeTab state and per-tab data properties to Insights.php

**Files:**
- Modify: `app/Filament/App/Pages/Insights.php`

- [ ] **Step 1: Add activeTab and per-tab data properties**

After `public string $successRate = '0';`, add:

```php
public string $activeTab = 'overview';

/**
 * @var array<int, array{month: string, amount: string, successRate: string, averageAmount: string}>
 */
public array $monthlyRevenue = [];

/**
 * @var array<int, array{campaign: string, total: string, donationCount: int, successRate: string}>
 */
public array $campaignPerformance = [];

/**
 * @var array<int, array{status: string, count: int, label: string}>
 */
public array $subscriptionStatusDistribution = [];

/**
 * @var array<int, array{interval: string, count: int, total: string}>
 */
public array $subscriptionIntervalBreakdown = [];

/**
 * @var array{currentMrr: string, activeCount: int, newThisMonth: int, cancelledThisMonth: int}
 */
public array $mrrOverview = [];

/**
 * @var array<int, array{month: string, newSubs: int, cancelledSubs: int, totalActive: int}>
 */
public array $subscriptionTrend = [];

/**
 * @var array{totalDonors: int, repeatDonors: int, repeatRate: string, newThisMonth: int, returningThisMonth: int}
 */
public array $retentionOverview = [];

/**
 * @var array<int, array{brand: string, count: int, percentage: float}>
 */
public array $paymentBrandBreakdown = [];

/**
 * @var array<int, array{type: string, count: int, total: string, percentage: float}>
 */
public array $paymentTypeBreakdown = [];

/**
 * @var array<int, array{name: string, type: string, campaign: string, isActive: bool}>
 */
public array $elementsList = [];

/**
 * @var array<int, array{campaign: string, totalDonations: int, totalAmount: string, donationCount: int}>
 */
public array $campaignUrlPerformance = [];
```

- [ ] **Step 2: Add setActiveTab action method**

After `mount()` method, add:

```php
public function setActiveTab(string $tab): void
{
    $this->activeTab = $tab;
}
```

- [ ] **Step 3: Add per-tab builder method calls to mount()**

At the end of `mount()`, after existing data is loaded, add:

```php
$this->monthlyRevenue = $this->buildMonthlyRevenue($campaignIds->all());
$this->campaignPerformance = $this->buildCampaignPerformance($campaignIds->all());
$this->subscriptionStatusDistribution = $this->buildSubscriptionStatusDistribution($campaignIds->all());
$this->subscriptionIntervalBreakdown = $this->buildSubscriptionIntervalBreakdown($campaignIds->all());
$this->mrrOverview = $this->buildMrrOverview($campaignIds->all());
$this->subscriptionTrend = $this->buildSubscriptionTrend($campaignIds->all());
$this->retentionOverview = $this->buildRetentionOverview($campaignIds->all());
$this->paymentBrandBreakdown = $this->buildPaymentBrandBreakdown($campaignIds->all());
$this->paymentTypeBreakdown = $this->buildPaymentTypeBreakdown($campaignIds->all());
$this->elementsList = $this->buildElementsList();
$this->campaignUrlPerformance = $this->buildCampaignUrlPerformance($campaignIds->all());
```

- [ ] **Step 4: Add all builder methods**

Before the closing `}` of the class, add:

```php
/**
 * @param array<int, int> $campaignIds
 * @return array<int, array{month: string, amount: string, successRate: string, averageAmount: string}>
 */
private function buildMonthlyRevenue(array $campaignIds): array
{
    $raw = Donation::query()
        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
        ->selectRaw("SUM(CASE WHEN status = ? THEN gross_amount ELSE 0 END) as total", [DonationStatus::Succeeded->value])
        ->selectRaw("COUNT(*) as total_count")
        ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success_count", [DonationStatus::Succeeded->value])
        ->whereIn('campaign_id', $campaignIds)
        ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->keyBy('month');

    return collect(range(11, 0))
        ->map(fn(int $i) => now()->subMonthsNoOverflow($i)->format('Y-m'))
        ->reverse()
        ->values()
        ->map(fn(string $month) => [
            'month' => now()->parse($month . '-01')->format('M Y'),
            'amount' => $this->formatMoney((float) ($raw[$month]->total ?? 0)),
            'successRate' => isset($raw[$month]) && $raw[$month]->total_count > 0
                ? round(($raw[$month]->success_count / $raw[$month]->total_count) * 100) . '%'
                : '0%',
            'averageAmount' => $this->formatMoney(
                isset($raw[$month]) && $raw[$month]->success_count > 0
                    ? (float) $raw[$month]->total / $raw[$month]->success_count
                    : 0
            ),
        ])
        ->all();
}

/**
 * @param array<int, int> $campaignIds
 * @return array<int, array{campaign: string, total: string, donationCount: int, successRate: string}>
 */
private function buildCampaignPerformance(array $campaignIds): array
{
    return Donation::query()
        ->selectRaw('campaign_id')
        ->selectRaw('SUM(CASE WHEN status = ? THEN gross_amount ELSE 0 END) as total', [DonationStatus::Succeeded->value])
        ->selectRaw('COUNT(*) as total_count')
        ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success_count', [DonationStatus::Succeeded->value])
        ->whereIn('campaign_id', $campaignIds)
        ->groupBy('campaign_id')
        ->orderByDesc('total')
        ->limit(10)
        ->get()
        ->map(fn($row) => [
            'campaign' => Campaign::find($row->campaign_id)?->title ?? 'Unknown',
            'total' => 'MYR ' . $this->formatMoney((float) $row->total),
            'donationCount' => (int) $row->success_count,
            'successRate' => $row->total_count > 0
                ? round(($row->success_count / $row->total_count) * 100) . '%'
                : '0%',
        ])
        ->all();
}

/**
 * @param array<int, int> $campaignIds
 * @return array<int, array{status: string, count: int, label: string}>
 */
private function buildSubscriptionStatusDistribution(array $campaignIds): array
{
    return collect(SubscriptionStatus::cases())
        ->map(fn(SubscriptionStatus $status) => [
            'status' => $status->value,
            'count' => Subscription::query()
                ->whereIn('campaign_id', $campaignIds)
                ->where('status', $status)
                ->count(),
            'label' => str($status->value)->headline()->toString(),
        ])
        ->all();
}

/**
 * @param array<int, int> $campaignIds
 * @return array<int, array{interval: string, count: int, total: string}>
 */
private function buildSubscriptionIntervalBreakdown(array $campaignIds): array
{
    return collect(SubscriptionInterval::cases())
        ->map(fn(SubscriptionInterval $interval) => [
            'interval' => str($interval->value)->headline()->toString(),
            'count' => Subscription::query()
                ->whereIn('campaign_id', $campaignIds)
                ->where('status', SubscriptionStatus::Active)
                ->where('interval', $interval)
                ->count(),
            'total' => 'MYR ' . $this->formatMoney((float) Subscription::query()
                ->whereIn('campaign_id', $campaignIds)
                ->where('status', SubscriptionStatus::Active)
                ->where('interval', $interval)
                ->sum('amount')),
        ])
        ->all();
}

/**
 * @param array<int, int> $campaignIds
 * @return array{currentMrr: string, activeCount: int, newThisMonth: int, cancelledThisMonth: int}
 */
private function buildMrrOverview(array $campaignIds): array
{
    $activeSubs = Subscription::query()->whereIn('campaign_id', $campaignIds)->where('status', SubscriptionStatus::Active);

    $mrr = (float) (clone $activeSubs)->where('interval', 'monthly')->sum('amount');

    $newThisMonth = Subscription::query()
        ->whereIn('campaign_id', $campaignIds)
        ->where('created_at', '>=', now()->startOfMonth())
        ->count();

    $cancelledThisMonth = Subscription::query()
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', SubscriptionStatus::Cancelled)
        ->where('cancelled_at', '>=', now()->startOfMonth())
        ->count();

    return [
        'currentMrr' => 'MYR ' . $this->formatMoney($mrr),
        'activeCount' => (clone $activeSubs)->count(),
        'newThisMonth' => $newThisMonth,
        'cancelledThisMonth' => $cancelledThisMonth,
    ];
}

/**
 * @param array<int, int> $campaignIds
 * @return array<int, array{month: string, newSubs: int, cancelledSubs: int, totalActive: int}>
 */
private function buildSubscriptionTrend(array $campaignIds): array
{
    $subscriptions = Subscription::query()
        ->whereIn('campaign_id', $campaignIds)
        ->get();

    return collect(range(5, 0))
        ->map(fn(int $i) => now()->subMonthsNoOverflow($i)->startOfMonth())
        ->reverse()
        ->values()
        ->map(function ($monthStart) use ($subscriptions) {
            $monthEnd = (clone $monthStart)->endOfMonth();

            $newSubs = $subscriptions
                ->filter(fn(Subscription $s) => $s->created_at->between($monthStart, $monthEnd))
                ->count();

            $cancelledSubs = $subscriptions
                ->filter(fn(Subscription $s) => $s->cancelled_at !== null && $s->cancelled_at->between($monthStart, $monthEnd))
                ->count();

            $totalActive = $subscriptions
                ->filter(fn(Subscription $s) => $s->status === SubscriptionStatus::Active && $s->created_at <= $monthEnd)
                ->count();

            return [
                'month' => $monthStart->format('M Y'),
                'newSubs' => $newSubs,
                'cancelledSubs' => $cancelledSubs,
                'totalActive' => $totalActive,
            ];
        })
        ->all();
}

/**
 * @param array<int, int> $campaignIds
 * @return array{totalDonors: int, repeatDonors: int, repeatRate: string, newThisMonth: int, returningThisMonth: int}
 */
private function buildRetentionOverview(array $campaignIds): array
{
    $donorDonationCounts = Donation::query()
        ->selectRaw('donor_id')
        ->selectRaw('COUNT(*) as donation_count')
        ->selectRaw('MIN(created_at) as first_donation')
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', DonationStatus::Succeeded)
        ->groupBy('donor_id')
        ->get();

    $totalDonors = $donorDonationCounts->count();
    $repeatDonors = $donorDonationCounts->filter(fn($d) => $d->donation_count > 1)->count();

    $newThisMonth = $donorDonationCounts
        ->filter(fn($d) => $d->first_donation >= now()->startOfMonth())
        ->count();

    $returningThisMonth = Donation::query()
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', DonationStatus::Succeeded)
        ->where('created_at', '>=', now()->startOfMonth())
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
        'repeatRate' => $totalDonors > 0 ? round(($repeatDonors / $totalDonors) * 100) . '%' : '0%',
        'newThisMonth' => $newThisMonth,
        'returningThisMonth' => $returningThisMonth,
    ];
}

/**
 * @param array<int, int> $campaignIds
 * @return array<int, array{brand: string, count: int, percentage: float}>
 */
private function buildPaymentBrandBreakdown(array $campaignIds): array
{
    $total = Donation::query()
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', DonationStatus::Succeeded)
        ->whereNotNull('payment_method_brand')
        ->count();

    $rows = Donation::query()
        ->selectRaw('payment_method_brand')
        ->selectRaw('COUNT(*) as count')
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', DonationStatus::Succeeded)
        ->whereNotNull('payment_method_brand')
        ->groupBy('payment_method_brand')
        ->orderByDesc('count')
        ->get()
        ->map(fn($row) => [
            'brand' => str($row->payment_method_brand)->headline()->toString(),
            'count' => (int) $row->count,
            'percentage' => $total > 0 ? round(((int) $row->count / $total) * 100, 1) : 0,
        ])
        ->all();

    return $rows;
}

/**
 * @param array<int, int> $campaignIds
 * @return array<int, array{type: string, count: int, total: string, percentage: float}>
 */
private function buildPaymentTypeBreakdown(array $campaignIds): array
{
    $total = Donation::query()
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', DonationStatus::Succeeded)
        ->whereNotNull('payment_method_type')
        ->count();

    return Donation::query()
        ->selectRaw('payment_method_type')
        ->selectRaw('COUNT(*) as count')
        ->selectRaw('SUM(gross_amount) as total_amount')
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', DonationStatus::Succeeded)
        ->whereNotNull('payment_method_type')
        ->groupBy('payment_method_type')
        ->orderByDesc('count')
        ->get()
        ->map(fn($row) => [
            'type' => str($row->payment_method_type)->headline()->toString(),
            'count' => (int) $row->count,
            'total' => 'MYR ' . $this->formatMoney((float) $row->total_amount),
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
        ->map(fn(Element $element) => [
            'name' => $element->name,
            'type' => str($element->type->value)->headline()->toString(),
            'campaign' => $element->campaign?->title ?? '-',
            'isActive' => $element->is_active,
        ])
        ->all();
}

/**
 * @param array<int, int> $campaignIds
 * @return array<int, array{campaign: string, totalDonations: int, totalAmount: string, donationCount: int}>
 */
private function buildCampaignUrlPerformance(array $campaignIds): array
{
    return Donation::query()
        ->selectRaw('campaign_id')
        ->selectRaw('COUNT(*) as total_donations')
        ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as successful_count', [DonationStatus::Succeeded->value])
        ->selectRaw('SUM(CASE WHEN status = ? THEN gross_amount ELSE 0 END) as total_amount', [DonationStatus::Succeeded->value])
        ->whereIn('campaign_id', $campaignIds)
        ->groupBy('campaign_id')
        ->orderByDesc('total_amount')
        ->limit(10)
        ->get()
        ->map(fn($row) => [
            'campaign' => Campaign::find($row->campaign_id)?->title ?? 'Unknown',
            'totalDonations' => (int) $row->total_donations,
            'totalAmount' => 'MYR ' . $this->formatMoney((float) $row->total_amount),
            'donationCount' => (int) $row->successful_count,
        ])
        ->all();
}
```

- [ ] **Step 5: Add missing imports**

At the top of the file, add missing imports:

```php
use App\Models\Element;
use App\Enums\SubscriptionInterval;
```

- [ ] **Step 6: Verify the file parses correctly**

Run: `php artisan tinker --execute 'echo class_exists(App\Filament\App\Pages\Insights::class) ? "ok" : "fail";'`
Expected: `ok`

---

### Task 2: Rewrite Blade view with tabbed navigation

**Files:**
- Modify: `resources/views/filament/app/pages/insights.blade.php`

- [ ] **Step 1: Rewrite the full Blade view**

Replace the entire file content with the tabbed navigation layout.

```blade
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Date Last 7 days
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Aggregation Daily
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Campaign All
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Source Direct + UTM
            </span>
            <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                Frequency All
            </span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total raised</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalRaised }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $successfulDonationsCount }} successful donations</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Recurring revenue</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $monthlyRecurringRevenue }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $activeRecurringDonors }} active recurring donors</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">One-time donations</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $oneTimeDonationsTotal }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Average MYR {{ $averageDonationAmount }}</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">First installments</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $firstInstallmentsTotal }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $successRate }}% payment success rate</div>
            </x-filament::section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <x-filament::section>
                @switch($activeTab)
                    @case('performance')
                        @include('filament.app.pages.insights-tabs.performance')
                    @case('recurring-plans')
                        @include('filament.app.pages.insights-tabs.recurring-plans')
                    @case('recurring-revenue')
                        @include('filament.app.pages.insights-tabs.recurring-revenue')
                    @case('retention')
                        @include('filament.app.pages.insights-tabs.retention')
                    @case('payment-methods')
                        @include('filament.app.pages.insights-tabs.payment-methods')
                    @case('frequencies')
                        @include('filament.app.pages.insights-tabs.frequencies')
                    @case('elements')
                        @include('filament.app.pages.insights-tabs.elements')
                    @case('url')
                        @include('filament.app.pages.insights-tabs.url')
                    @default
                        {{-- Overview --}}
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Overview</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Performance over the last 7 days</p>
                            </div>
                            <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                                Metric Revenue
                            </div>
                        </div>

                        <div class="mt-8 flex h-64 items-end gap-3 border-b border-gray-200 pb-2 dark:border-gray-700">
                            @foreach ($dailyRevenue as $point)
                                <div class="flex h-full min-w-0 flex-1 flex-col justify-end gap-2" wire:key="revenue-{{ $point['label'] }}">
                                    <div
                                        class="rounded-t-md bg-primary-500/80"
                                        style="height: {{ $point['height'] }}%"
                                        title="{{ $point['label'] }}: MYR {{ $point['amount'] }}"
                                    ></div>
                                    <div class="truncate text-center text-xs text-gray-500 dark:text-gray-400">{{ $point['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 grid gap-6 xl:grid-cols-2">
                            <div>
                                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Status breakdown</h3>
                                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($statusBreakdown as $row)
                                        <div class="flex items-center justify-between py-3 text-sm" wire:key="status-{{ $row['label'] }}">
                                            <span class="text-gray-500 dark:text-gray-400">{{ $row['label'] }}</span>
                                            <span class="font-medium text-gray-950 dark:text-white">{{ $row['value'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Recent donations</h3>
                                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($recentDonations as $donation)
                                        <div class="flex items-center justify-between gap-4 py-3" wire:key="recent-{{ $donation['donor'] }}-{{ $loop->index }}">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $donation['donor'] }}</div>
                                                <div class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $donation['campaign'] }} &middot; {{ $donation['type'] }}</div>
                                            </div>
                                            <div class="shrink-0 text-sm font-semibold text-gray-950 dark:text-white">{{ $donation['amount'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                @endswitch
            </x-filament::section>

            <div class="space-y-3">
                @php
                    $tabs = [
                        'overview' => 'Overview',
                        'performance' => 'Performance',
                        'recurring-plans' => 'Recurring plans',
                        'recurring-revenue' => 'Recurring revenue',
                        'retention' => 'Retention',
                        'payment-methods' => 'Payment methods',
                        'frequencies' => 'Frequencies',
                        'elements' => 'Elements',
                        'url' => 'URL',
                    ];
                @endphp

                @foreach ($tabs as $key => $label)
                    <div
                        wire:click="setActiveTab('{{ $key }}')"
                        class="{{ $activeTab === $key ? 'border-primary-500 text-gray-950 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:border-gray-600 dark:hover:text-gray-300' }} cursor-pointer border-l-2 px-3 py-1.5 text-sm font-semibold transition-colors"
                    >
                        {{ $label }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
```

---

### Task 3: Create per-tab Blade partials

**Files:**
- Create: `resources/views/filament/app/pages/insights-tabs/performance.blade.php`
- Create: `resources/views/filament/app/pages/insights-tabs/recurring-plans.blade.php`
- Create: `resources/views/filament/app/pages/insights-tabs/recurring-revenue.blade.php`
- Create: `resources/views/filament/app/pages/insights-tabs/retention.blade.php`
- Create: `resources/views/filament/app/pages/insights-tabs/payment-methods.blade.php`
- Create: `resources/views/filament/app/pages/insights-tabs/frequencies.blade.php`
- Create: `resources/views/filament/app/pages/insights-tabs/elements.blade.php`
- Create: `resources/views/filament/app/pages/insights-tabs/url.blade.php`

- [ ] **Step 1: Create directory**

```bash
mkdir -p resources/views/filament/app/pages/insights-tabs
```

- [ ] **Step 2: Create `performance.blade.php`**

```blade
<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Performance</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Monthly revenue and campaign performance</p>
        </div>
    </div>

    <div class="mt-8">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Monthly Revenue (12 months)</h3>
        <div class="mt-4 flex h-48 items-end gap-2 border-b border-gray-200 pb-2 dark:border-gray-700">
            @php
                $maxAmount = max(1, ...array_map(fn($m) => (float) $m['amount'], $monthlyRevenue));
            @endphp
            @foreach ($monthlyRevenue as $month)
                <div class="flex h-full min-w-0 flex-1 flex-col justify-end gap-1" wire:key="mrev-{{ $month['month'] }}">
                    <div
                        class="rounded-t-md bg-primary-500/80"
                        style="height: {{ max(8, round(((float) $month['amount'] / $maxAmount) * 100)) }}%"
                        title="{{ $month['month'] }}: MYR {{ $month['amount'] }}"
                    ></div>
                    <div class="truncate text-center text-xs text-gray-500 dark:text-gray-400">{{ $month['month'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        @if (count($campaignPerformance) > 0)
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Campaign Comparison</h3>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($campaignPerformance as $camp)
                        <div class="flex items-center justify-between py-3 text-sm" wire:key="camp-{{ $camp['campaign'] }}">
                            <span class="truncate text-gray-500 dark:text-gray-400">{{ $camp['campaign'] }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $camp['total'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Trends</h3>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($monthlyRevenue as $month)
                    <div class="flex items-center justify-between py-2 text-sm" wire:key="trend-{{ $month['month'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $month['month'] }}</span>
                        <div class="text-right">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $month['successRate'] }} success</div>
                            <div class="text-xs text-gray-400">Avg MYR {{ $month['averageAmount'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Create `recurring-plans.blade.php`**

```blade
<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Recurring Plans</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Subscription status and interval breakdown</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Status Distribution</h3>
            <div class="mt-4 space-y-3">
                @foreach ($subscriptionStatusDistribution as $item)
                    <div class="flex items-center justify-between text-sm" wire:key="ssd-{{ $item['status'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                        <span class="font-medium text-gray-950 dark:text-white">{{ $item['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">By Interval</h3>
            <div class="mt-4 space-y-3">
                @forelse ($subscriptionIntervalBreakdown as $item)
                    <div class="flex items-center justify-between text-sm" wire:key="int-{{ $item['interval'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $item['interval'] }}</span>
                        <div class="text-right">
                            <div class="font-medium text-gray-950 dark:text-white">{{ $item['count'] }}</div>
                            <div class="text-xs text-gray-400">{{ $item['total'] }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No subscription data yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 4: Create `recurring-revenue.blade.php`**

```blade
<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Recurring Revenue</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">MRR and subscription activity trends</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Current MRR</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $mrrOverview['currentMrr'] }}</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $mrrOverview['activeCount'] }} active subscriptions</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-green-600 dark:text-green-400">New this month</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $mrrOverview['newThisMonth'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-red-600 dark:text-red-400">Cancelled this month</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $mrrOverview['cancelledThisMonth'] }}</div>
        </x-filament::section>
    </div>

    @if (count($subscriptionTrend) > 0)
        <div class="mt-8">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Subscription Trend (6 months)</h3>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($subscriptionTrend as $trend)
                    <div class="flex items-center justify-between py-3 text-sm" wire:key="trend-{{ $trend['month'] }}">
                        <span class="text-gray-500 dark:text-gray-400">{{ $trend['month'] }}</span>
                        <div class="flex gap-6 text-right">
                            <div>
                                <div class="text-xs text-gray-400">New</div>
                                <div class="font-medium text-green-600 dark:text-green-400">{{ $trend['newSubs'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">Cancelled</div>
                                <div class="font-medium text-red-600 dark:text-red-400">{{ $trend['cancelledSubs'] }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">Active</div>
                                <div class="font-medium text-gray-950 dark:text-white">{{ $trend['totalActive'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
```

- [ ] **Step 5: Create `retention.blade.php`**

```blade
<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Retention</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Donor loyalty and repeat giving</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-4">
        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Donors</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $retentionOverview['totalDonors'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Repeat Donors</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $retentionOverview['repeatDonors'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Repeat Rate</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $retentionOverview['repeatRate'] }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">New This Month</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $retentionOverview['newThisMonth'] }}</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $retentionOverview['returningThisMonth'] }} returning</div>
        </x-filament::section>
    </div>
</div>
```

- [ ] **Step 6: Create `payment-methods.blade.php`**

```blade
<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Payment Methods</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Card brand and payment type distribution</p>
        </div>
    </div>

    <div class="mt-8 grid gap-8 md:grid-cols-2">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Card Brands</h3>
            <div class="mt-4 space-y-4">
                @forelse ($paymentBrandBreakdown as $brand)
                    <div wire:key="brand-{{ $brand['brand'] }}">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">{{ $brand['brand'] }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $brand['count'] }} ({{ $brand['percentage'] }}%)</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ $brand['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No payment method data yet</p>
                @endforelse
            </div>
        </div>

        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Payment Types</h3>
            <div class="mt-4 space-y-4">
                @forelse ($paymentTypeBreakdown as $type)
                    <div wire:key="ptype-{{ $type['type'] }}">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">{{ $type['type'] }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $type['total'] }} ({{ $type['percentage'] }}%)</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ $type['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No payment type data yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 7: Create `frequencies.blade.php`**

```blade
<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Frequencies</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">One-time vs recurring donation breakdown</p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @foreach ($frequencyBreakdown as $row)
            <x-filament::section wire:key="freq-{{ $row['label'] }}">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $row['label'] }}</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $row['value'] }}</div>
            </x-filament::section>
        @endforeach
    </div>
</div>
```

- [ ] **Step 8: Create `elements.blade.php`**

```blade
<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Elements</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Embed buttons, forms, and popups</p>
        </div>
    </div>

    @if (count($elementsList) > 0)
        <div class="mt-8 divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($elementsList as $element)
                <div class="flex items-center justify-between py-3 text-sm" wire:key="elem-{{ $element['name'] }}">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-950 dark:text-white">
                            {{ $element['name'] }}
                            @if ($element['isActive'])
                                <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-800/30 dark:text-green-400">Active</span>
                            @else
                                <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Inactive</span>
                            @endif
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $element['type'] }} &middot; {{ $element['campaign'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-sm text-gray-400">No donation attribution data yet for individual elements.</p>
    @else
        <div class="mt-8 flex items-center justify-center rounded-lg border border-dashed border-gray-300 p-12 dark:border-gray-700">
            <p class="text-sm text-gray-400">No elements created yet. Create embed buttons, forms, or popups from the Elements section.</p>
        </div>
    @endif
</div>
```

- [ ] **Step 9: Create `url.blade.php`**

```blade
<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">URL</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Campaign donation page performance</p>
        </div>
    </div>

    @if (count($campaignUrlPerformance) > 0)
        <div class="mt-8 divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($campaignUrlPerformance as $camp)
                <div class="flex items-center justify-between py-3 text-sm" wire:key="url-{{ $camp['campaign'] }}">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $camp['campaign'] }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $camp['totalDonations'] }} total donations</div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium text-gray-950 dark:text-white">{{ $camp['totalAmount'] }}</div>
                        <div class="text-xs text-gray-400">{{ $camp['donationCount'] }} succeeded</div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="mt-8 flex items-center justify-center rounded-lg border border-dashed border-gray-300 p-12 dark:border-gray-700">
            <p class="text-sm text-gray-400">No donation data yet for campaigns.</p>
        </div>
    @endif
</div>
```

---

### Task 4: Update tests

**Files:**
- Modify: `tests/Feature/Ihsan/InsightsPageTest.php`

- [ ] **Step 1: Read existing test file**

Run: `cat tests/Feature/Ihsan/InsightsPageTest.php`

- [ ] **Step 2: Add tab switching assertion and new data assertions**

Add after existing assertions:
```php
// Tab switching
Livewire::test(Insights::class)
    ->assertSet('activeTab', 'overview')
    ->call('setActiveTab', 'performance')
    ->assertSet('activeTab', 'performance')
    ->call('setActiveTab', 'recurring-plans')
    ->assertSet('activeTab', 'recurring-plans');

// Performance data exists
$component = Livewire::test(Insights::class);
expect($component->monthlyRevenue)->toBeArray();
expect($component->campaignPerformance)->toBeArray();

// Payment methods data
expect($component->paymentBrandBreakdown)->toBeArray();
expect($component->paymentTypeBreakdown)->toBeArray();

// Retention data
expect($component->retentionOverview['totalDonors'])->toBeInt();
expect($component->retentionOverview['repeatRate'])->toBeString();

// Recurring plans data
expect($component->subscriptionStatusDistribution)->toBeArray();
expect($component->subscriptionIntervalBreakdown)->toBeArray();

// MRR data
expect($component->mrrOverview['currentMrr'])->toBeString();
expect($component->mrrOverview['newThisMonth'])->toBeInt();

// Elements data
expect($component->elementsList)->toBeArray();

// URL data
expect($component->campaignUrlPerformance)->toBeArray();
```

- [ ] **Step 3: Run the test**

Run: `php artisan test --compact --filter=InsightsPageTest`
Expected: PASS

---

### Task 5: Run Pint

- [ ] **Step 1: Run Pint to fix code style**

Run: `vendor/bin/pint --format agent`
Expected: No errors or auto-fixed

- [ ] **Step 2: Run tests again to confirm**

Run: `php artisan test --compact --filter=InsightsPageTest`
Expected: PASS
