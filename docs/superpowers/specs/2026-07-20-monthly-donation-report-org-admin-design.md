# Monthly Donation Report (Org Admin) — Design

## Goal
Add a self-service monthly donation collection report page in the `/app` panel so organization admins can preview, filter by month/date range, and download donation data as CSV and PDF.

## Context
- The `/app` panel currently has no dedicated report page. Admins can view donations lists and dashboard summaries, but cannot export/download a formatted report.
- The dashboard has a period selector (today, this month, etc.), but no export action.
- Billing already calculates month-based fee/gross/net summaries (`App\Livewire\App\Billing`).
- Super admin revenue reports already use `Barryvdh\DomPDF` for PDF and streamed CSV downloads (`RevenueReportController`).

## Scope

### In Scope
- New page `/app/reports/monthly-donations`.
- Sidebar navigation item under a new "Reports" group.
- Two date-selection modes:
  1. **Monthly (default)** — dropdown of month/year, defaulting to current month.
  2. **Custom range** — optional `Date from` and `Date to` inputs.
- Summary cards: Total Gross, Processing Fee, Net Received, Total Donations, Unique Donors.
- Campaign breakdown table: Campaign, Donations, Gross, Processing Fee, Net.
- CSV download (raw rows + summary).
- PDF download (formatted document with header, summary, and table).

### Out of Scope
- Scheduled email delivery (email command `ihsan:send-monthly-report` already exists; no change).
- Refund/subscription-specific sections.
- Charts or trend graphs.

## Data Model

### Source Table
`donations` filtered by:
- `status = DonationStatus::Succeeded`
- Associated campaign belongs to the authenticated user's organization.
- `created_at` between selected date range.
- Columns used:
  - `gross_amount`
  - `processing_fee`
  - `stripe_fee`
  - `net_amount`
  - `campaign_id`
  - `donor_id`

### Calculations
- **Processing Fee** = `processing_fee + stripe_fee`
- **Net Received** = `net_amount`
- **Total Gross** = `gross_amount`
- **Unique Donors** = `COUNT(DISTINCT donor_id)`

## UI/UX

### Layout
```
[Page Title]
[Period Selector: Monthly | Custom Range] [Download CSV] [Download PDF]

Summary Cards (5):
  Total Gross      Processing Fee      Net Received      Total Donations      Unique Donors

Breakdown by Campaign:
  | Campaign | Donations | Gross (MYR) | Processing Fee (MYR) | Net (MYR) |
```

### Period Selector Modes
- When **Monthly** is selected, show a single `Month / Year` dropdown.
- When **Custom range** is enabled, show `Date from` and `Date to` inputs; summary/table update live.
- Summary and table must recompute when the period changes.

### Navigation
- Sidebar group: **Reports**
- Item: **Monthly Donations**
- URL: `/app/reports/monthly-donations`
- Route name: `app.reports.monthly-donations`

## Architecture

### Files to Create
1. `app/Livewire/App/Reports/MonthlyDonations.php` — main Livewire component, query logic, computed properties.
2. `resources/views/livewire/app/reports/monthly-donations.blade.php` — UI for the report page.
3. `resources/views/exports/monthly-donations-report.blade.php` — PDF template.
4. `tests/Feature/App/Reports/MonthlyDonationReportTest.php` — feature tests.

### Files to Modify
1. `routes/app.php` — register report route inside `EnsureNgoAdmin` group.
2. `resources/views/components/layouts/app/sidebar.blade.php` (or equivalent navigation partial) — add Reports menu and Monthly Donations link.

### Permission & Security
- Use existing `auth` + `EnsureNgoAdmin` middleware.
- Scope every query to `Auth::user()->organization_id` via `campaign.organization_id`.
- Do not expose other organizations' data.

## CSV Format
```csv
Organization,Example Org
Public ID,ORG12345
Report type,Monthly Donations
Period,July 2026
Generated at,2026-07-20 10:30:00 (MYT)

Summary
Metric,Value
Total Gross,12345.67
Processing Fee,370.37
Net Received,11975.30
Total Donations,42
Unique Donors,38

Campaign Breakdown
Campaign,Donations,Gross (MYR),Processing Fee (MYR),Net (MYR)
Bantuan Banjir,20,5000.00,150.00,4850.00
Tabung Pendidikan,22,7345.67,220.37,7125.30
```

## PDF Format
- A4 portrait.
- Header: organization name, public ID, report title, generated datetime.
- Summary section as a compact table.
- Campaign breakdown table with striped rows.
- Footer: page number and "Generated from Ihsan".

## Dependencies
- `barryvdh/laravel-dompdf` (already installed).
- `league/csv` is not required; PHP native `fputcsv` will be used to match `RevenueReportController`.

## Testing
1. Verify page loads for authenticated NGO admin.
2. Verify data is scoped to the admin's organization only.
3. Verify default period is current month.
4. Verify custom date range updates summary and table.
5. Verify CSV response status 200, correct content-type, and expected rows.
6. Verify PDF response status 200, content-type `application/pdf`, and contains organization name.
7. Verify empty state renders gracefully.
