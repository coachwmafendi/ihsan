# Donor Portal Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Dashboard page to the donor portal with stats cards, monthly Chart.js bar chart, campaign breakdown, and recent activity.

**Architecture:** New `dashboard()` method on `DonorPortalController` → new `donor.dashboard` Blade view → Chart.js renders the monthly chart client-side from JSON data. Dashboard tab added to the existing nav layout.

**Tech Stack:** Laravel, Blade, Chart.js, Tailwind CSS, Alpine.js (existing)

---

### Task 1: Remove `/donorportal/` redirect and add dashboard route

**Files:**
- Modify: `routes/web.php:29`
- Modify: `app/Http/Controllers/DonorPortalController.php`

- [ ] **Step 1: Update route — change `/donorportal/` redirect to go to dashboard**

In `routes/web.php:29`, change:

```php
Route::get('/', fn () => redirect()->route('donorportal.login'));
```

To:

```php
Route::get('/', fn () => redirect()->route('donorportal.dashboard'));
```

- [ ] **Step 2: Add the dashboard route before the donations route**

In `routes/web.php`, add after the logout route:

```php
Route::get('dashboard', [DonorPortalController::class, 'dashboard'])->name('dashboard');
```

- [ ] **Step 3: Add `dashboard()` method to `DonorPortalController`**

Add this method before `donations()`:

```php
public function dashboard()
{
    $donor = $this->getDonor();
    if ($donor === null) {
        return redirect()->route('donorportal.login');
    }

    $totalGiven = $donor->donations()
        ->where('status', DonationStatus::Succeeded)
        ->sum('gross_amount');

    $activeSubscriptions = $donor->subscriptions()
        ->where('status', SubscriptionStatus::Active)
        ->count();

    $monthlyRecurring = $donor->subscriptions()
        ->where('status', SubscriptionStatus::Active)
        ->sum('amount');

    $monthlyDonations = $donor->donations()
        ->where('status', DonationStatus::Succeeded)
        ->selectRaw("strftime('%Y-%m', created_at) as month, SUM(gross_amount) as total")
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->limit(12)
        ->get()
        ->reverse()
        ->values();

    $campaignBreakdown = $donor->donations()
        ->where('status', DonationStatus::Succeeded)
        ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
        ->selectRaw('campaigns.title as campaign, SUM(donations.gross_amount) as total')
        ->groupBy('campaigns.title')
        ->orderByDesc('total')
        ->get();

    $recentDonations = $donor->donations()
        ->where('status', DonationStatus::Succeeded)
        ->with('campaign.organization')
        ->latest()
        ->limit(5)
        ->get();

    return view('donor.dashboard', [
        'donor' => $donor,
        'totalGiven' => $totalGiven,
        'activeSubscriptions' => $activeSubscriptions,
        'monthlyRecurring' => $monthlyRecurring,
        'monthlyDonations' => $monthlyDonations,
        'campaignBreakdown' => $campaignBreakdown,
        'recentDonations' => $recentDonations,
    ]);
}
```

- [ ] **Step 4: Add Chart.js CDN to the layout or view**

No change to layout — we'll add the Chart.js script tag directly in the dashboard view via a `@push('scripts')` or inline in `@section('content')`.

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/pint --format agent
```

---

### Task 2: Add Dashboard tab to nav layout

**Files:**
- Modify: `resources/views/donor/layout.blade.php:26-37`

- [ ] **Step 1: Add Dashboard as the first nav item**

Replace lines 26-37:

```blade
            <nav class="flex gap-1 rounded-xl bg-stone-100/80 p-1.5">
                <a href="{{ route('donorportal.dashboard') }}"
                   class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-medium transition
                   {{ request()->routeIs('donorportal.dashboard') ? 'bg-white text-emerald-700 shadow-sm' : 'text-stone-500 hover:text-stone-700' }}">
                    Dashboard
                </a>
                <a href="{{ route('donorportal.donations') }}"
                   class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-medium transition
                   {{ request()->routeIs('donorportal.donations') ? 'bg-white text-emerald-700 shadow-sm' : 'text-stone-500 hover:text-stone-700' }}">
                    Donations
                </a>
                <a href="{{ route('donorportal.subscriptions') }}"
                   class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-medium transition
                   {{ request()->routeIs('donorportal.subscriptions') ? 'bg-white text-emerald-700 shadow-sm' : 'text-stone-500 hover:text-stone-700' }}">
                    Subscriptions
                </a>
            </nav>
```

---

### Task 3: Create the Dashboard view

**Files:**
- Create: `resources/views/donor/dashboard.blade.php`

- [ ] **Step 1: Create the dashboard Blade view**

```blade
@extends('donor.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Dashboard</h1>
        <p class="mt-1 text-sm text-stone-500">Here's an overview of your giving.</p>
    </div>

    <div class="mb-8 grid grid-cols-3 gap-4">
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">Total Given</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">RM {{ number_format($totalGiven, 2) }}</p>
        </div>
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">Active Subscriptions</p>
            <p class="mt-2 text-3xl font-bold text-stone-900">{{ $activeSubscriptions }}</p>
        </div>
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">Monthly Recurring</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">RM {{ number_format($monthlyRecurring, 2) }}<span class="text-base font-normal text-stone-400">/mo</span></p>
        </div>
    </div>

    <div class="mb-8">
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="mb-4 text-sm font-semibold text-stone-900">Monthly Giving (Last 12 Months)</p>
            <canvas id="monthlyChart" height="100"></canvas>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-2 gap-6">
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="mb-4 text-sm font-semibold text-stone-900">By Campaign</p>
            @forelse ($campaignBreakdown as $item)
                <div class="mb-3 flex items-center gap-3 last:mb-0">
                    <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                    <span class="flex-1 text-sm text-stone-700">{{ $item->campaign }}</span>
                    <span class="text-sm font-semibold text-stone-900">RM {{ number_format($item->total, 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-stone-400">No donations yet.</p>
            @endforelse
        </div>
        <div class="rounded-xl border border-stone-200/70 bg-white p-6">
            <p class="mb-4 text-sm font-semibold text-stone-900">Recent Activity</p>
            @forelse ($recentDonations as $donation)
                <div class="mb-3 border-b border-stone-100 pb-3 last:mb-0 last:border-0 last:pb-0">
                    <p class="text-sm font-medium text-stone-900">{{ $donation->campaign->title }}</p>
                    <p class="text-xs text-stone-400">RM {{ number_format($donation->gross_amount, 2) }} &middot; {{ $donation->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="text-sm text-stone-400">No activity yet.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = @json($monthlyDonations);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [{
                    label: 'Giving',
                    data: monthlyData.map(d => d.total),
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => 'RM ' + value.toLocaleString(),
                        },
                        grid: { color: '#e5e5e5' },
                    },
                    x: {
                        grid: { display: false },
                    }
                }
            }
        });
    </script>
@endpush
```

---

### Task 4: Add `@stack('scripts')` to layout

**Files:**
- Modify: `resources/views/donor/layout.blade.php`

- [ ] **Step 1: Add `@stack('scripts')` before `</body>`**

In `resources/views/donor/layout.blade.php`, add before `</body>`:

```blade
    @stack('scripts')
```

---

### Task 5: Test the dashboard

- [ ] **Step 1: Run tests to ensure no regressions**

```bash
php artisan test --compact
```

- [ ] **Step 2: Visit the dashboard in browser**

Open https://ihsan.test/donorportal/dashboard and verify stats, chart, and content render correctly.

- [ ] **Step 3: Run Pint**

```bash
vendor/bin/pint --format agent
```
