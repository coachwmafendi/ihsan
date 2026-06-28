# CHIP Recurring (Phase 2) Design

## Goal
Add monthly recurring donation support for campaigns that use the **CHIP** payment gateway, reusing the existing `Subscription` model and scheduler infrastructure.

## Constraints
- Keep current donor form UX: only **One-time** vs **Monthly**.
- CHIP recurring is card-based only (`visa`, `mastercard`, `maestro`).
- No new database table; extend `subscriptions` and `donations`.
- No dependency changes; continue using Laravel HTTP client via `ChipApi`.
- Follow the existing app-controlled recurring pattern already used by Stripe.

## Data model changes

### `subscriptions`
| Change | Purpose |
| --- | --- |
| Ensure `chip_recurring_token` is in `$fillable` | Stores CHIP recurring token (the paid Purchase ID that saved the card). |
| Optional: add `chip_client_id` nullable | Groups tokens under a CHIP Client object if we later need list/delete APIs by client. |

### `donations`
Existing `chip_purchase_id` and `chip_invoice_id` are sufficient. Each renewal installment creates a new `Donation` row linked to the subscription.

## First payment flow

1. Donor selects **Monthly** and submits.
2. `DonationForm::submitChip()` creates a pending `Donation` with `type = Recurring`.
3. `CreatePurchase` builds a CHIP Purchase with:
   - `force_recurring: true`
   - `payment_method_whitelist: ['visa', 'mastercard', 'maestro']`
   - `success_redirect` / `failure_redirect` pointing to `/chip/callback`
4. Donor pays on CHIP checkout and is redirected back.
5. `ChipCheckoutController::callback()` renders the bridge page.
6. Frontend calls `POST /chip/confirm/{donation}` which runs `ConfirmPurchase`.
7. `ConfirmPurchase`:
   - Verifies Purchase status is `paid`.
   - Finalizes the first `Donation` (status, fees, campaign increment).
   - If `type === Recurring` **and** no `Subscription` exists, creates a `Subscription`:
     - `chip_recurring_token` = initial CHIP Purchase ID
     - `status = Active`
     - `interval = SubscriptionInterval::Monthly`
     - `payment_count = 1`
     - `current_period_start = now`
     - `current_period_end = nextChargeAt`
     - `next_charge_at = SubscriptionSchedule::nextChargeAt(Monthly, now)`
     - `cover_fee = false` for now
   - Links the donation: `donation->subscription_id = subscription->id`.
   - Dispatches `SendNewSubscriptionNotification` and `SendDonorNewSubscriptionNotification` if it is a new subscription.

## Renewal flow

Scheduled by the existing `app/Console/Commands/ChargeDueRecurringPlans.php` command.

### Scheduler logic update
The selector already finds active due subscriptions. The dispatched job (`ChargeRecurringInstallment`) must be gateway-aware:
- If `stripe_subscription_id` is set → existing Stripe path.
- If `chip_recurring_token` is set → new CHIP path.
- If neither → skip / log warning.

### CHIP renewal action
1. Create a new CHIP Purchase for the installment amount.
2. Immediately call `POST /purchases/{new_purchase_id}/charge/` with `{ "recurring_token": "<chip_recurring_token>" }`.
3. If the charge response status is `paid`:
   - Create a new `Donation` linked to the subscription.
   - Finalize the donation (fees, campaign increment, receipt).
   - Call `ScheduleRetry::afterSuccess($subscription)` to advance `next_charge_at`, `payment_count`, etc.
4. If the charge fails:
   - Call `ScheduleRetry::afterFailure($subscription)` for retries / status changes.

All CHIP API calls must be faked in tests using `Http::fake()`.

## Cancellation flow

Both **admin** and **donor portal** can cancel a CHIP subscription.

1. User clicks Cancel.
2. Controller calls a new action `App\Actions\Chip\CancelRecurringToken`:
   - Calls `POST /purchases/{chip_recurring_token}/delete_recurring_token/`.
   - Updates subscription:
     - `status = SubscriptionStatus::Cancelled`
     - `cancelled_at = now`
     - `cancel_at_period_end = false`
3. If the CHIP API call fails, still mark subscription cancelled locally but report the error.

## Refund (out of scope for this phase)
Refunds remain a separate future phase. This design only touches recurring subscription lifecycle.

## Routes and controllers

No new route needed for the first payment (reuse `/chip/callback`).

Potential additions:
- `POST /chip/subscriptions/{subscription:public_id}/cancel` — donor/admin cancel (reuse existing subscription policy).

## UI changes

- No changes to the public donation form frequency selector (one-time vs monthly only).
- In donor/admin portal, reuse existing subscription cancel button; backend will detect CHIP vs Stripe and call the correct action.

## Testing

1. CHIP monthly first payment creates an active subscription with `chip_recurring_token`.
2. Renewal scheduler creates a new donation and advances the subscription dates.
3. Failed renewal increments retry/failed counts and sets status appropriately.
4. Donor/admin cancel calls CHIP delete token endpoint and marks subscription cancelled.

## Files likely changed
- `app/Models/Subscription.php`
- `app/Services/ChipApi.php`
- `app/Actions/Chip/CreatePurchase.php`
- `app/Actions/Chip/ConfirmPurchase.php`
- `app/Actions/Chip/CancelRecurringToken.php` (new)
- `app/Actions/Chip/ChargeRecurringInstallment.php` (new)
- `app/Console/Commands/ChargeDueRecurringPlans.php`
- `app/Actions/Stripe/ChargeRecurringInstallment.php` (refactor to detect gateway)
- `routes/web.php`
- `tests/Feature/ChipRecurringDonationTest.php` (new)
