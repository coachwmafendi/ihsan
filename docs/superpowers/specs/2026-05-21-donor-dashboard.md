# Donor Portal Dashboard

## Overview
Add a Dashboard page to the donor portal with statistics and Chart.js-powered charts, as a new tab alongside Donations and Subscriptions.

## Route

- `/donorportal/dashboard` — `GET`, named `donorportal.dashboard`
- `/donorportal/` — redirects to `donorportal.dashboard` (was redirecting to login)

## Controller

Add `dashboard()` method to `DonorPortalController`. Guards via `getDonor()` same as existing methods.

### Data passed to view

- `$donor` — current donor
- `$totalGiven` — sum of all succeeded donations
- `$activeSubscriptions` — count of active subscriptions
- `$monthlyRecurring` — sum of all active subscription amounts
- `$monthlyDonations` — collection of `{month: string, total: float}` for last 12 months (succeeded donations grouped by month)
- `$campaignBreakdown` — collection of `{campaign: string, total: float}` for succeeded donations grouped by campaign
- `$recentDonations` — latest 5 succeeded donations with campaign & organization

## View

New file `resources/views/donor/dashboard.blade.php` extending `donor.layout`.

### Sections

1. **Hero stat cards** — 3 cards in a 3-col grid:
   - Total Given (formatted currency)
   - Active Subscriptions (count)
   - Monthly Recurring (formatted currency with `/mo` suffix)

2. **Monthly Giving chart** — full-width `<canvas>` rendered via Chart.js bar chart (last 12 months, emerald color scheme)

3. **Bottom split row** — 2-col grid:
   - Left: Campaign breakdown list (colored dots, campaign name, total)
   - Right: Recent activity list (campaign name, amount, relative time)

### Chart.js

- Use Chart.js from CDN (`https://cdn.jsdelivr.net/npm/chart.js`), or from the already-bundled Filament Chart.js asset path if available. Check whether there's a simpler Vite import path.
- If CDN is cleaner, add the script tag to the dashboard view only (not layout).
- Chart renders on page load via a small inline script reading data from a JSON-serialized PHP variable.

## Nav

Add "Dashboard" as the first tab in `donor.layout` nav, active when `request()->routeIs('donorportal.dashboard')`.

## Logo (already done)
The brand logo change is already completed from the previous task.

## Verification
- Visit `/donorportal/dashboard` as a logged-in donor with data
- Verify stats cards show correct numbers
- Verify monthly chart renders bars for months with data
- Verify campaign list and recent activity are populated
- Run `php artisan test` to ensure no regressions
- Run `vendor/bin/pint --format agent` for code style
