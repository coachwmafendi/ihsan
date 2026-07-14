# Revenue Page — Per-Organization Report Download

## Goal
Enable platform admins to download a monthly/periodic revenue report for each organization directly from the **Revenue by Organization** table on `/admin/revenue`.

## Context
- Existing page: `App\Filament\Pages\Revenue` (`resources/views/filament/admin/pages/revenue.blade.php`).
- Page already computes per-organization revenue breakdown (donations, volume, fees, effective rate) based on the selected period.
- Existing PDF engine: `barryvdh/laravel-dompdf`.
- No Excel package is installed; CSV will be generated natively.

## Design

### UI
- Add an **Export** column at the right end of the “Revenue by Organization” table.
- Each row gets two small icon-only actions: **CSV** and **PDF**.
- Keep the rest of the page unchanged.

### Interactions
- Clicking **CSV** triggers a Livewire action `downloadCsv(string $organizationPublicId)`.
- Clicking **PDF** triggers a Livewire action `downloadPdf(string $organizationPublicId)`.
- Both actions locate the organization by `public_id`, recalculate the data for the currently selected period, and return a file download response.

### File Naming
```
ihsan-{org_public_id}-{sanitized_org_name}-revenue-{period}-{Y-m-d}.csv
ihsan-{org_public_id}-{sanitized_org_name}-revenue-{period}-{Y-m-d}.pdf
```
- `sanitized_org_name`: lowercase, alphanumeric + hyphen, spaces replaced with `-`, special characters stripped (max ~50 chars).
- Example: `ihsan-O2E8X1A-my-organisation-revenue-last_month-2026-07-14.pdf`

### Report Content
Both formats contain the same data for the selected period and organization:

1. **Report header**
   - Organization name
   - Organization public ID
   - Report period label (e.g. “Last month — 1 Jul 2026 to 31 Jul 2026”)
   - Generated at timestamp

2. **Summary metrics**
   - Total donations (count)
   - Donation volume (gross succeeded donations)
   - Average donation size
   - Total Stripe / processing fees
   - Effective fee rate (%)

3. **Breakdown table** (CSV rows / PDF table)
   - The single organization row is repeated in a clean table view, so the PDF/CSV is self-contained.

### Architecture
- Keep download logic in the existing `App\Filament\Pages\Revenue` page class.
- Extract reusable formatting into a small private method: `organizationReportData(Organization $org): array`.
- CSV export: use PHP’s built-in `fputcsv` inside a `StreamedResponse`.
- PDF export: use `Barryvdh\DomPDF\Facade\Pdf` with a dedicated Blade view `filament.admin.exports.revenue-organization-report`.

### Access Control
- Page is already behind the admin panel auth gate; no extra policy needed.
- Only existing admin users can access `/admin/revenue` and therefore the download actions.

### Testing Plan
- Feature test: visit `/admin/revenue`, click CSV/PDF for an org, assert download response has expected filename and content type.
- Feature test: assert report body contains the org name, period label, and donation volume.
- Feature test: assert selecting a different period updates the downloaded report values.

## Out of Scope
- Bulk export for all organizations at once.
- Scheduled/automated email delivery.
- Excel format (no package installed).
