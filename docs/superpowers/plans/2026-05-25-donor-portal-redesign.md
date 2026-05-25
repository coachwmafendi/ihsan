# Donor Portal Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rewrite all 5 donor portal Blade views with a Bold Minimal aesthetic — dark top nav, white cards, heavy typography, emerald accents.

**Architecture:** Pure frontend rework of existing Blade views. No PHP, no routes, no controller changes. Each view is rewritten in place. Tests assert on rendered HTML to validate design elements render correctly.

**Tech Stack:** Blade templates, TailwindCSS v4, Chart.js (CDN, dashboard only), Pest feature tests

---

## Files

| Action | Path | What changes |
|--------|------|--------------|
| Modify | `resources/views/donor/layout.blade.php` | Dark top nav, donor initials avatar, new page shell |
| Modify | `resources/views/donor/login.blade.php` | Standalone login — `Ihsan.` wordmark, slate submit button |
| Modify | `resources/views/donor/dashboard.blade.php` | 3-stat row → full-width chart → 2-col breakdown |
| Modify | `resources/views/donor/donations.blade.php` | 2-stat header → donation card list with badges |
| Modify | `resources/views/donor/subscriptions.blade.php` | Subscription cards with status badges + cancel button |
| Modify | `tests/Feature/DonorPortalTest.php` | Add design-element assertions to existing tests |

---

## Task 1: Layout — dark top nav + page shell

**Files:**
- Modify: `resources/views/donor/layout.blade.php`
- Modify: `tests/Feature/DonorPortalTest.php`

- [ ] **Step 1: Add failing assertions to existing tests**

Open `tests/Feature/DonorPortalTest.php`. Add `assertSee('Ihsan.')` and nav link assertions to the two page-render tests:

```php
it('shows donation history for authenticated donor', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations'))
        ->assertOk()
        ->assertSee('Ihsan.')
        ->assertSee('Dashboard')
        ->assertSee('Donations')
        ->assertSee('Subscriptions')
        ->assertSee($donor->name);
});

it('shows subscriptions for authenticated donor', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.subscriptions'))
        ->assertOk()
        ->assertSee('Ihsan.')
        ->assertSee('Subscriptions');
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
php artisan test --compact --filter="shows donation history|shows subscriptions"
```

Expected: FAIL — `assertSee('Ihsan.')` fails because layout has `ihsan` (lowercase, no dot).

- [ ] **Step 3: Rewrite layout.blade.php**

Replace the entire file with:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Donor Portal') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 antialiased">
    <header class="bg-slate-900">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-6 py-3">
            <a href="{{ route('donorportal.dashboard') }}"
               class="text-sm font-black text-white [letter-spacing:-0.02em]">
                Ihsan.
            </a>
            <nav class="flex gap-1">
                <a href="{{ route('donorportal.dashboard') }}"
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.dashboard') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Dashboard
                </a>
                <a href="{{ route('donorportal.donations') }}"
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.donations') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Donations
                </a>
                <a href="{{ route('donorportal.subscriptions') }}"
                   class="rounded-md px-3 py-1.5 text-xs font-medium transition
                   {{ request()->routeIs('donorportal.subscriptions') ? 'border border-emerald-500/30 bg-emerald-500/15 font-bold text-emerald-400' : 'text-white/40 hover:text-white/70' }}">
                    Subscriptions
                </a>
            </nav>
            @php
                $nameParts = array_values(array_filter(explode(' ', trim($donor->name))));
                $initials = strtoupper(substr($nameParts[0] ?? '?', 0, 1));
                if (count($nameParts) > 1) {
                    $initials .= strtoupper(substr(end($nameParts), 0, 1));
                }
            @endphp
            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-[10px] font-bold text-white">
                {{ $initials }}
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
php artisan test --compact --filter="shows donation history|shows subscriptions"
```

Expected: PASS

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add resources/views/donor/layout.blade.php tests/Feature/DonorPortalTest.php
git commit -m "redesign: donor portal layout — dark top nav, Ihsan. wordmark, initials avatar"
```

---

## Task 2: Login page

**Files:**
- Modify: `resources/views/donor/login.blade.php`
- Modify: `tests/Feature/DonorPortalTest.php`

- [ ] **Step 1: Add failing login page test**

Add to `tests/Feature/DonorPortalTest.php`:

```php
it('renders login page with new design', function () {
    $this->get(route('donorportal.login'))
        ->assertOk()
        ->assertSee('Ihsan.')
        ->assertSee('Your giving, your way.')
        ->assertSee('Send Login Link');
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test --compact --filter="renders login page"
```

Expected: FAIL — `assertSee('Ihsan.')` fails (current has `ihsan`, no dot).

- [ ] **Step 3: Rewrite login.blade.php**

Replace the entire file with:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 px-4 antialiased">
    <div class="w-full max-w-xs">
        <div class="mb-8">
            <p class="text-2xl font-black text-slate-900 [letter-spacing:-0.03em]">Ihsan.</p>
            <p class="mt-1 text-xs text-slate-400">Your giving, your way.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-[0_4px_24px_rgba(15,23,42,0.08)]"
             style="border-width:1.5px;">
            <h1 class="text-base font-black text-slate-900">Welcome back</h1>
            <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
                Enter your email and we'll send a magic link — no password needed.
            </p>

            @if (session('success'))
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-semibold leading-relaxed text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs leading-relaxed text-red-600">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('donorportal.send-magic-link') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700">
                        Email address
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        autofocus
                        value="{{ old('email') }}"
                        placeholder="donor@example.com"
                        class="mt-1.5 block w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none"
                        style="border-width:1.5px;"
                    />
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 active:bg-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2"
                >
                    Send Login Link →
                </button>
            </form>
        </div>
    </div>
</body>
</html>
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
php artisan test --compact --filter="renders login page"
```

Expected: PASS

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add resources/views/donor/login.blade.php tests/Feature/DonorPortalTest.php
git commit -m "redesign: donor portal login — bold minimal, slate CTA, magic link form"
```

---

## Task 3: Dashboard page

**Files:**
- Modify: `resources/views/donor/dashboard.blade.php`
- Modify: `tests/Feature/DonorPortalTest.php`

- [ ] **Step 1: Add failing dashboard test**

Add to `tests/Feature/DonorPortalTest.php`:

```php
it('renders dashboard with stats and activity sections', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Total Given')
        ->assertSee('Active Plans')
        ->assertSee('Monthly')
        ->assertSee('Monthly Giving')
        ->assertSee('By Campaign')
        ->assertSee('Recent Activity');
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test --compact --filter="renders dashboard"
```

Expected: FAIL — `assertSee('Active Plans')` fails (current has "Active Subscriptions").

- [ ] **Step 3: Rewrite dashboard.blade.php**

Replace the entire file with:

```blade
@extends('donor.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Dashboard</h1>
        <p class="mt-0.5 text-xs text-slate-500">Hi {{ $donor->name }}, here's your giving overview.</p>
    </div>

    <div class="mb-6 grid grid-cols-3 gap-3">
        <div class="rounded-xl bg-white p-4" style="border:1.5px solid #e2e8f0;">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Given</p>
            <p class="mt-1.5 text-xl font-black text-emerald-700">RM {{ number_format($totalGiven, 2) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4" style="border:1.5px solid #e2e8f0;">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Active Plans</p>
            <p class="mt-1.5 text-xl font-black text-slate-900">{{ $activeSubscriptions }}</p>
        </div>
        <div class="rounded-xl bg-white p-4" style="border:1.5px solid #e2e8f0;">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Monthly</p>
            <p class="mt-1.5 text-xl font-black text-emerald-700">RM {{ number_format($monthlyRecurring, 2) }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
        <h2 class="mb-4 text-sm font-bold text-slate-900">Monthly Giving</h2>
        <canvas id="monthlyChart" height="80"></canvas>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
            <h2 class="mb-4 text-sm font-bold text-slate-900">By Campaign</h2>
            @if ($campaignBreakdown->isNotEmpty())
                @php $dotColors = ['#10b981', '#0ea5e9', '#8b5cf6', '#f59e0b', '#ef4444']; @endphp
                <div class="space-y-3">
                    @foreach ($campaignBreakdown as $i => $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 flex-shrink-0 rounded-full"
                                      style="background:{{ $dotColors[$i % count($dotColors)] }};"></span>
                                <p class="text-xs font-semibold text-slate-700">{{ $item->campaign }}</p>
                            </div>
                            <p class="text-xs font-black text-slate-900">RM {{ number_format($item->total, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400">No donations yet.</p>
            @endif
        </div>

        <div class="rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
            <h2 class="mb-4 text-sm font-bold text-slate-900">Recent Activity</h2>
            @if ($recentDonations->isNotEmpty())
                <div class="divide-y divide-slate-50">
                    @foreach ($recentDonations as $donation)
                        <div class="flex items-center justify-between py-2 first:pt-0 last:pb-0">
                            <div class="min-w-0 flex-1 pr-3">
                                <p class="truncate text-xs font-bold text-slate-900">{{ $donation->campaign->title }}</p>
                                <p class="text-[11px] text-slate-400">{{ $donation->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="flex-shrink-0 text-xs font-black text-slate-900">
                                RM {{ number_format($donation->gross_amount, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400">No activity yet.</p>
            @endif
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
                    data: monthlyData.map(d => d.total),
                    backgroundColor: monthlyData.map((d, i) =>
                        i === monthlyData.length - 1 ? '#10b981' : '#f1f5f9'
                    ),
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => 'RM ' + v.toLocaleString(),
                            font: { size: 10 },
                        },
                        grid: { color: '#f1f5f9' },
                        border: { display: false },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } },
                        border: { display: false },
                    },
                },
            },
        });
    </script>
@endpush
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
php artisan test --compact --filter="renders dashboard"
```

Expected: PASS

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add resources/views/donor/dashboard.blade.php tests/Feature/DonorPortalTest.php
git commit -m "redesign: donor portal dashboard — stat cards, bar chart, 2-col campaign/activity"
```

---

## Task 4: Donations page

**Files:**
- Modify: `resources/views/donor/donations.blade.php`
- Modify: `tests/Feature/DonorPortalTest.php`

- [ ] **Step 1: Add failing donations page test**

Add to `tests/Feature/DonorPortalTest.php`:

```php
it('renders donations page with stats and card list', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations'))
        ->assertOk()
        ->assertSee('Total Given')
        ->assertSee('Donations')
        ->assertSee('Your complete giving history.');
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test --compact --filter="renders donations page"
```

Expected: FAIL — `assertSee('Your complete giving history.')` fails (current subtitle is different).

- [ ] **Step 3: Rewrite donations.blade.php**

Replace the entire file with:

```blade
@extends('donor.layout')

@section('title', 'Donations')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Donations</h1>
        <p class="mt-0.5 text-xs text-slate-500">Your complete giving history.</p>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3">
        <div class="rounded-xl bg-white p-4" style="border:1.5px solid #e2e8f0;">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Given</p>
            <p class="mt-1.5 text-xl font-black text-emerald-700">RM {{ number_format($totalGiven, 2) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4" style="border:1.5px solid #e2e8f0;">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Donations</p>
            <p class="mt-1.5 text-xl font-black text-slate-900">{{ $donationCount }}</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($donations as $donation)
            <div class="rounded-xl bg-white p-4 transition hover:shadow-md" style="border:1.5px solid #e2e8f0;">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-900">{{ $donation->campaign->title }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $donation->campaign->organization->name }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-base font-black text-slate-900">RM {{ number_format($donation->gross_amount, 2) }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-400">{{ $donation->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    @php
                        $statusClass = match ($donation->status) {
                            \App\Enums\DonationStatus::Succeeded => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            \App\Enums\DonationStatus::Pending   => 'bg-amber-50 text-amber-700 border-amber-200',
                            \App\Enums\DonationStatus::Failed    => 'bg-red-50 text-red-600 border-red-200',
                            \App\Enums\DonationStatus::Refunded  => 'bg-slate-50 text-slate-600 border-slate-200',
                        };
                        $statusLabel = ($donation->status === \App\Enums\DonationStatus::Succeeded ? '✓ ' : '')
                            . str($donation->status->value)->headline();
                        $typeClass = $donation->type === \App\Enums\DonationType::Recurring
                            ? 'bg-blue-50 text-blue-700 border-blue-200'
                            : 'bg-amber-50 text-amber-700 border-amber-200';
                        $typeLabel = $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time';
                    @endphp
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $typeClass }}">
                        {{ $typeLabel }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-xl bg-white px-8 py-16 text-center" style="border:1.5px solid #e2e8f0;">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                    </svg>
                </div>
                <p class="text-sm font-bold text-slate-700">No donations yet</p>
                <p class="mt-1.5 text-xs text-slate-500">Your giving history will appear here once you make a donation.</p>
            </div>
        @endforelse
    </div>

    @if ($donations->hasPages())
        <div class="mt-8">{{ $donations->links() }}</div>
    @endif
@endsection
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
php artisan test --compact --filter="renders donations page"
```

Expected: PASS

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add resources/views/donor/donations.blade.php tests/Feature/DonorPortalTest.php
git commit -m "redesign: donor portal donations — stat header, card list, status/type badges"
```

---

## Task 5: Subscriptions page

**Files:**
- Modify: `resources/views/donor/subscriptions.blade.php`
- Modify: `tests/Feature/DonorPortalTest.php`

- [ ] **Step 1: Add failing subscriptions page test**

Add to `tests/Feature/DonorPortalTest.php`:

```php
it('renders subscriptions page with manage subtitle', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.subscriptions'))
        ->assertOk()
        ->assertSee('Subscriptions')
        ->assertSee('Manage your recurring donations.');
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test --compact --filter="renders subscriptions page"
```

Expected: FAIL — current subtitle text is different.

- [ ] **Step 3: Rewrite subscriptions.blade.php**

Replace the entire file with:

```blade
@extends('donor.layout')

@section('title', 'Subscriptions')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Subscriptions</h1>
        <p class="mt-0.5 text-xs text-slate-500">Manage your recurring donations.</p>
    </div>

    <div class="space-y-3">
        @forelse ($subscriptions as $subscription)
            <div class="rounded-xl bg-white p-4 transition {{ $subscription->status === \App\Enums\SubscriptionStatus::Cancelled ? 'opacity-60' : 'hover:shadow-md' }}"
                 style="border:1.5px solid #e2e8f0;">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-900">{{ $subscription->campaign->title }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $subscription->campaign->organization->name }}</p>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-base font-black text-slate-900">
                            RM {{ number_format($subscription->amount, 2) }}<span class="text-xs font-normal text-slate-400">/{{ $subscription->interval->value }}</span>
                        </p>
                        @if ($subscription->current_period_end)
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                Next: {{ $subscription->current_period_end->format('d M Y') }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    @php
                        $statusClass = match ($subscription->status) {
                            \App\Enums\SubscriptionStatus::Active     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            \App\Enums\SubscriptionStatus::Cancelled  => 'bg-slate-50 text-slate-600 border-slate-200',
                            \App\Enums\SubscriptionStatus::PastDue    => 'bg-red-50 text-red-600 border-red-200',
                            \App\Enums\SubscriptionStatus::Paused     => 'bg-amber-50 text-amber-700 border-amber-200',
                            \App\Enums\SubscriptionStatus::Incomplete => 'bg-slate-50 text-slate-500 border-slate-200',
                        };
                        $statusPrefix = $subscription->status === \App\Enums\SubscriptionStatus::Active ? '● ' : '';
                    @endphp
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusClass }}">
                        {{ $statusPrefix }}{{ str($subscription->status->value)->headline() }}
                    </span>

                    @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                        <form action="{{ route('donorportal.subscriptions.cancel', $subscription) }}"
                              method="POST"
                              onsubmit="return confirm('Cancel this subscription?')">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-100">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                                Cancel
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl bg-white px-8 py-16 text-center" style="border:1.5px solid #e2e8f0;">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                    <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" />
                    </svg>
                </div>
                <p class="text-sm font-bold text-slate-700">No subscriptions yet</p>
                <p class="mt-1.5 text-xs text-slate-500">Set up a recurring donation to start a subscription.</p>
            </div>
        @endforelse
    </div>

    @if ($subscriptions->hasPages())
        <div class="mt-8">{{ $subscriptions->links() }}</div>
    @endif
@endsection
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
php artisan test --compact --filter="renders subscriptions page"
```

Expected: PASS

- [ ] **Step 5: Run full test suite**

```bash
php artisan test --compact tests/Feature/DonorPortalTest.php
```

Expected: All tests PASS

- [ ] **Step 6: Run pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add resources/views/donor/subscriptions.blade.php tests/Feature/DonorPortalTest.php
git commit -m "redesign: donor portal subscriptions — status badges, cancel button, empty state"
```

---

## Task 6: Visual verification

- [ ] **Step 1: Open login page in browser**

Navigate to `https://ihsan.test/donorportal/login` and verify:
- `Ihsan.` wordmark visible, slate-900 colour
- "Your giving, your way." tagline below
- White card on light gradient bg
- "Send Login Link →" slate button

- [ ] **Step 2: Log in and verify dashboard**

Log in as a donor with existing data and verify:
- Dark top nav with `Ihsan.`, 3 nav links, initials avatar
- Active nav link has emerald highlight
- 3 stat cards in a row
- Bar chart renders (emerald current month, slate past months)
- 2-col bottom: By Campaign dots + Recent Activity rows

- [ ] **Step 3: Check donations page**

Navigate to `/donorportal/donations` and verify:
- 2-stat header row
- Donation cards with status badge (emerald Succeeded) + type badge (blue Recurring / amber One-time)
- Hover shadow on cards

- [ ] **Step 4: Check subscriptions page**

Navigate to `/donorportal/subscriptions` and verify:
- Active subscription: emerald `● Active` badge + red Cancel button
- Cancelled subscription: greyed out (opacity-60), no cancel button

- [ ] **Step 5: Final full test run**

```bash
php artisan test --compact tests/Feature/DonorPortalTest.php
```

Expected: All tests PASS. No regressions.
