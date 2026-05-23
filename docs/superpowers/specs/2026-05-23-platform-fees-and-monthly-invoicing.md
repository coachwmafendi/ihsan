# Platform Fees & Monthly NGO Invoicing

## Overview

Ihsan currently deducts platform fees (2.5%) from each donation via Stripe Connect's `application_fee_amount`. The new model records the fee per-transaction without deducting it (NGO receives the full donation), accumulates all fees, and bills the NGO monthly via Stripe Invoice.

## Changes

### 1. Payment Flow — Stop Deducting Fees Per-Transaction

**Files to change:**

**`app/Actions/Stripe/CreatePaymentIntent.php`** (line 62):
- Remove `'application_fee_amount'` from PaymentIntent params
- NGO's connected account receives the full `gross_amount`

**`app/Actions/Stripe/CreateRecurringSubscription.php`** (line 136):
- Remove `'application_fee_percent'` from Stripe Subscription params
- Recurring donations also pass through fully to NGO

**`app/Actions/Stripe/SyncDonationStripeDetails.php`**:
- `net_amount` should now be `gross_amount - stripe_fee` (not deducting `platform_fee`)
- The `platform_fee` column on `donations` still records the calculated fee for reporting

### 2. PlatformFee Record Creation

A `PlatformFee` record must be created every time a donation succeeds.

**Where to create:** In `SyncDonationStripeDetails.php`, after calculating the platform fee, create a `PlatformFee` record linked to the donation and organization. This runs both in the Livewire `confirmPayment()` flow and the `ProcessStripeWebhook` job (for recurring invoice payments).

PlatformFee model fields:
- `donation_id` — FK to donations
- `organization_id` — FK to organizations
- `fee_amount` — calculated fee
- `fee_percentage` — snapshot of rate at transaction time (from config)
- `stripe_transfer_id` — now unused initially, recorded when invoice is paid
- `status` — `pending` (accrued, not yet invoiced)

**New statuses for PlatformFee (replacing existing `pending|transferred|failed`):**
- `pending` — accrued, awaiting monthly invoicing
- `invoiced` — included in a Stripe Invoice
- `paid` — invoice has been paid
- `failed` — error during processing

**Add a migration** to alter the `platform_fees.status` column to accept `pending|invoiced|paid|failed`.

### 3. Admin UI — Platform Fees Page

**New page:** `App\Filament\Pages\PlatformFees`

**Navigation:** Between Transactions (sort 15) and Revenue (sort 18)

**View:** Table with columns:
- Date (created_at)
- NGO name (organization.name)
- Campaign title
- Donor name
- Donation amount (gross_amount)
- Fee amount (fee_amount)
- Fee percentage
- Status (badge: pending/invoiced/paid)
- Invoice reference (if invoiced)

**Filters:**
- NGO (SelectFilter → Organization::pluck('name', 'id'))
- Status (SelectFilter → pending/invoiced/paid)
- Date range (DatePicker from/to)

**Actions per-row (if status is pending):**
- "Mark as Paid" — for manual fee recording outside Stripe

**Bulk actions:**
- Export CSV

### 4. Admin UI — Monthly Invoices Page

**New page:** `App\Filament\Pages\MonthlyInvoices`

**Navigation:** After Platform Fees (sort 19)

**Header stats cards:**
- Total Outstanding (sum of invoice amounts where stripe_status = open/unpaid)
- Total Collected (sum of invoice amounts where stripe_status = paid)
- Invoices Sent This Month (count)

**Table columns:**
- Invoice number (generated: `INV-YYYYMM-NNN`)
- NGO name
- Period (e.g. "May 2026")
- Total fees amount (MYR)
- Stripe Invoice ID (link to Stripe dashboard)
- Stripe status (open/paid/uncollectible/void)
- Paid at
- Created at

**Filters:**
- Month/period
- NGO
- Stripe status

**Actions:**
- "Generate Invoice" button — manual trigger (calls the same logic as the scheduled command)

### 5. Monthly Invoice Generation Command

**New command:** `php artisan ihsan:generate-monthly-invoices`

**Logic:**
1. Query all PlatformFee records with `status = 'pending'` from the previous calendar month
2. Group by `organization_id`
3. For each NGO with accumulated fees > 0:
   a. Create a `Stripe\Invoice` on the platform's Stripe account
   b. Add an invoice item with description: "Ihsan Platform Fees — [month] [year]"
   c. Finalize and send the invoice (Stripe sends email to the NGO's contact email)
   d. Store the Stripe Invoice ID and update all PlatformFee records to `invoiced`
   e. Record the invoice in a new `monthly_invoices` database table

**New model:** `MonthlyInvoice`
- `organization_id` — FK
- `stripe_invoice_id` — Stripe Invoice ID
- `invoice_number` — `INV-YYYYMM-NNN`
- `period` — date (first of the month, e.g. 2026-05-01)
- `total_fees` — decimal sum
- `stripe_status` — open/paid/uncollectible/void
- `paid_at` — nullable timestamp
- `stripe_invoice_url` — hosted invoice URL from Stripe

**Schedule:** In `routes/console.php`:
```php
Schedule::command('ihsan:generate-monthly-invoices')->monthlyOn(1, '08:00');
```

### 6. Webhook Handling — Invoice Payment Updates

Update `app/Jobs/ProcessStripeWebhook.php` to handle `invoice.paid` for platform invoices:

When `invoice.paid` arrives:
1. Check if the invoice is for our platform (not a connected account's invoice)
2. If `MonthlyInvoice` exists with this `stripe_invoice_id`:
   - Update `stripe_status` to `paid`
   - Set `paid_at` to now
   - Update all associated `PlatformFee` records to `paid`

### 7. New Database Tables / Migration

**`monthly_invoices` table:**
```sql
id                    BIGINT PRIMARY KEY
organization_id       BIGINT FK → organizations
stripe_invoice_id     STRING UNIQUE
invoice_number        STRING UNIQUE
period                DATE           -- first of month (e.g. 2026-05-01)
total_fees            DECIMAL(12,2)
stripe_status         STRING DEFAULT 'open' INDEX -- open|paid|uncollectible|void
paid_at               TIMESTAMP NULLABLE
stripe_invoice_url    STRING NULLABLE
stripe_invoice_pdf    STRING NULLABLE (URL to PDF)
created_at            TIMESTAMP
updated_at            TIMESTAMP
```

**Alter `platform_fees.status`:**
- Add `invoiced` and `paid` to allowed values
- Add `monthly_invoice_id` nullable FK column

### 8. Files to Modify (Summary)

| File | Change |
|------|--------|
| `app/Actions/Stripe/CreatePaymentIntent.php` | Remove `application_fee_amount` |
| `app/Actions/Stripe/CreateRecurringSubscription.php` | Remove `application_fee_percent` |
| `app/Actions/Stripe/SyncDonationStripeDetails.php` | Create PlatformFee record, update net_amount calc |
| `app/Models/PlatformFee.php` | Add casts for status enum, add invoice FK |
| `app/Models/MonthlyInvoice.php` | **NEW** model |
| `app/Filament/Pages/PlatformFees.php` | **NEW** admin page |
| `app/Filament/Pages/MonthlyInvoices.php` | **NEW** admin page |
| `resources/views/filament/admin/pages/platform-fees.blade.php` | **NEW** view (or use table, filament renders inline) |
| `resources/views/filament/admin/pages/monthly-invoices.blade.php` | **NEW** view |
| `app/Console/Commands/GenerateMonthlyInvoices.php` | **NEW** command |
| `app/Jobs/ProcessStripeWebhook.php` | Handle `invoice.paid` for platform invoices |
| `routes/console.php` | Schedule monthly command |
| `app/Providers/Filament/AdminPanelProvider.php` | Register new pages |
| `database/migrations/..._create_monthly_invoices_table.php` | **NEW** migration |
| `database/migrations/..._update_platform_fees_table.php` | **NEW** migration — update status allowed values (pending|invoiced|paid|failed), add `monthly_invoice_id` FK |

### 9. Testing

- Unit test for `GenerateMonthlyInvoices` command logic
- Unit test for PlatformFee creation in SyncDonationStripeDetails
- Feature test for Platform Fees page access and rendering
- Feature test for Monthly Invoices page access and rendering
- Test webhook handling for `invoice.paid` for platform invoices

### 10. Non-Goals

- No changes to donor-facing payment experience
- No changes to Stripe Connect onboarding
- No dunning/retry logic for failed NGO payments (Stripe handles this for invoices)
- No per-NGO custom fee rates
