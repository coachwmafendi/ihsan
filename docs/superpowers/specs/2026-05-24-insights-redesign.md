# Insights Page Redesign — Tabbed Analytics Dashboard

## Overview
Redesign the existing Insights page (`/app/insights`) from a single-scroll layout into a tabbed navigation dashboard with 9 sections. Each tab reveals its own analytics content without page reload, using Livewire state.

## Navigation Structure
```
[Overview] [Performance] [Recurring Plans] [Recurring Revenue] [Retention] [Payment Methods] [Frequencies] [Elements] [URL]
```

Sidebar on the right remains, now interactive — clicking a tab changes `$activeTab` Livewire property and swaps the main content area. Active tab has `border-primary-500` highlight. Excluded: UTM.

## Sections

### 1. Overview (Maintain + Improve)
- **4 KPI cards:** Total Raised, Recurring Revenue, One-time Donations, First Installments — unchanged
- **7-day bar chart:** Daily revenue bars, CSS-based, unchanged
- **Status breakdown:** Per DonationStatus count — unchanged
- **Recent donations:** Last 5 donations — unchanged

### 2. Performance (New)
- **Monthly Revenue Trend:** Bar chart — last 12 months, sum of gross_amount for succeeded donations, grouped by month
- **Campaign Comparison:** Bar chart — top campaigns by gross_amount
- **Success Rate Trend:** Per-month success percentage
- **Average Donation Trend:** Per-month average gross_amount

### 3. Recurring Plans (Expanded)
- **Status distribution:** Counts for Active, Past Due, Cancelled, Paused, Incomplete — with colored badges
- **Interval breakdown:** Count and total amount by interval (Weekly, Monthly, Yearly)

### 4. Recurring Revenue (New)
- **MRR:** Current month's sum of active monthly subscriptions
- **Active subscriptions trend:** Monthly count of active subscriptions over last 12 months
- **New vs Cancelled:** Subscriptions created vs cancelled per month (last 6 months)

### 5. Retention (New)
- **Total Donors:** Distinct donor count (org-scoped)
- **Repeat Donor Rate:** % of donors with >1 successful donation
- **New vs Returning:** Monthly cohort — first-time donors vs repeat donors

### 6. Payment Methods (Dynamic — replace hardcoded)
- **Card brand breakdown:** Visa, Mastercard, etc. from `donations.payment_method_brand` — count + percentage
- **Payment type:** Card vs Wallet from `donations.payment_method_type` — progress bars
- Real data, not hardcoded

### 7. Frequencies (Maintain)
- One-time vs Recurring total amounts — unchanged from current implementation

### 8. Elements (New)
- List active elements (type + campaign name)
- No direct donation attribution available yet — show "No attribution data yet" for performance metrics

### 9. URL (New)
- Top campaigns ranked by donation amount
- Campaign donation page slugs/URLs with performance

## Technical Design

### Component State
```php
public string $activeTab = 'overview';
```
All data computed once in `mount()` to avoid per-tab query overhead. Each data set stored in a typed array property.

### Computed Data Arrays
```php
public array $performanceData = [];     // Monthly revenue, campaign comparison, trends
public array $recurringPlansData = [];   // Status distribution, interval breakdown
public array $recurringRevenueData = []; // MRR trend, new vs cancelled
public array $retentionData = [];        // Repeat rate, new vs returning
public array $paymentMethodsData = [];   // Card brands, payment types
public array $elementsData = [];         // Active elements list
public array $urlData = [];              // Campaign performance
```

### Private Builders
Each data set has a dedicated private builder method, following the existing pattern (`buildDailyRevenue()`, `buildFrequencyBreakdown()`, etc.):
- `buildPerformanceData()` — 12-month revenue, campaign comparison, success/average trends
- `buildRecurringPlansData()` — status + interval aggregations
- `buildRecurringRevenueData()` — MRR + new/cancelled subscriptions
- `buildRetentionData()` — donor stats, repeat rate
- `buildPaymentMethodsData()` — brand + type breakdown
- `buildElementsData()` — active elements
- `buildUrlData()` — campaign ranking

### View Structure
```blade
<x-filament-panels::page>
    <!-- Filter chips (static, same as before) -->
    <!-- KPI cards (always visible, unchanged) -->
    
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
        <!-- Main content: switches on $activeTab -->
        <x-filament::section>
            @switch($activeTab)
                @case('performance') ...content...
                @case('recurring-plans') ...content...
                ...
                @default <!-- overview --> ...content...
            @endswitch
        </x-filament::section>
        
        <!-- Sidebar: interactive tab navigation -->
        @foreach(tabs as tab)
            <div wire:click="setActiveTab('{{ $tab }}')" ...
        @endforeach
    </div>
</x-filament-panels::page>
```

### Action Handler
```php
public function setActiveTab(string $tab): void
{
    $this->activeTab = $tab;
}
```

## Migration / Updates
- No migrations needed — all data from existing `donations`, `subscriptions`, `campaigns`, `elements` tables
- No new models or enums
- Only modifying: `Insights.php` and `insights.blade.php`

## Testing
- Update existing `InsightsPageTest.php` to assert tab navigation works
- Add assertions for performance, recurring plans, retention data correctness
