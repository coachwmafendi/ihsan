# Payouts Read-Only Page (Org Admin) — Design

## Goal
Build a self-service read-only Payouts page in the `/app` panel so organization admins can view their Stripe Connect payout history without editing payout settings.

## Context
- The `/app` panel has a placeholder route `/payouts` that currently redirects to the dashboard.
- Stripe Connect is the payment processor. Each organization has a connected Stripe account.
- Payout data originates from Stripe but should be cached locally for fast queries and reconciliation.

## Scope

### In Scope
- Replace placeholder `/payouts` route with a real Livewire page.
- Create a local `payouts` table to cache Stripe payout data.
- Sync payouts via Stripe API command and Stripe webhooks.
- Display payout history with filters (date range, status).
- Show summary cards: Paid This Month, Pending, Next Expected Payout.
- Detail includes: date, amount, status, destination bank last 4 digits.
- Read-only — no editing of bank account, frequency, or payout mode.

### Out of Scope
- Editing bank account details.
- Changing payout schedule / frequency / mode.
- Manual payout creation.
- Bank account verification flows.
- Linking individual donations to payouts (can be added later via balance transactions).

## Data Model

### `payouts` table
| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | Primary key |
| organization_id | foreignId | Belongs to organizations |
| stripe_payout_id | string | Unique Stripe payout ID (po_xxx) |
| amount | integer | In smallest currency unit (sen) |
| currency | string | Lowercase, e.g. `myr` |
| status | string | `pending`, `in_transit`, `paid`, `failed`, `canceled` |
| arrival_date | date | When Stripe expects/when it arrived in bank |
| paid_at | date/nullable | Final paid date |
| bank_name | string/nullable | e.g. `Maybank` |
| bank_account_last4 | string/nullable | e.g. `1234` |
| failure_code | string/nullable | Stripe failure code |
| failure_message | string/nullable | Stripe failure message |
| metadata | json/nullable | Raw Stripe metadata |
| timestamps | timestamps | created_at / updated_at |

Unique index on `[organization_id, stripe_payout_id]`.

## Architecture

### Sync Layer
1. **Stripe webhooks** — primary real-time updates:
   - `payout.created`
   - `payout.paid`
   - `payout.failed`
   - `payout.canceled`
2. **Console command** — backfill/scheduled sync:
   ```
   php artisan app:sync-payouts {--organization= : Specific organization public_id} {--days=90}
   ```

### Files to Create
1. `database/migrations/2026_07_20_000000_create_payouts_table.php`
2. `app/Models/Payout.php`
3. `app/Console/Commands/SyncPayouts.php`
4. `app/Actions/Stripe/SyncPayout.php` — single payout upsert action
5. `app/Livewire/App/Payouts.php` — page component
6. `resources/views/livewire/app/payouts.blade.php` — page UI
7. `tests/Feature/App/PayoutsTest.php` — feature tests

### Files to Modify
1. `app/Jobs/ProcessStripeWebhook.php` — add payout event handlers
2. `routes/app.php` — replace placeholder `/payouts` route
3. `resources/views/components/sidebar.blade.php` — add nav item under Finance (if not already present)

## UI/UX

### Layout
```
[Page Title: Payouts]
[Period selector] [Status filter]

Summary Cards:
  Paid This Month    Pending    Next Expected Payout

Table:
  | Date | Status | Amount | Bank Account |
```

### Status Badge Styles
- `pending` — yellow
- `in_transit` — blue
- `paid` — green
- `failed` / `canceled` — red

### Empty State
If no payouts found: "No payouts found. Payouts will appear once Stripe processes your first transfer."

## Security
- Query scope strictly by authenticated user's `organization_id`.
- Payout webhook events include `account` field; use it to resolve the correct organization by `stripe_account_id`.
- Never expose other organizations' payout data.

## Testing
1. Page loads for authenticated NGO admin.
2. Page shows synced payout rows.
3. Webhook handler creates/updates payout correctly.
4. Sync command backfills payouts from Stripe fixture/data.
5. Data is scoped to the admin's organization only.
