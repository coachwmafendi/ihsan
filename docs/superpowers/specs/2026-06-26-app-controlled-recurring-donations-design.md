# Design: App-Controlled Recurring Donations (Fasa 1)

**Date:** 2026-06-26  
**Status:** Draft — pending review  
**Scope:** Recurring donations created after this change will be charged by the application, not by Stripe Billing. Existing Stripe Subscriptions will be left to run out naturally (hybrid cut-over).  

---

## 1. Problem Statement

Ihsan currently uses **Stripe Billing Subscriptions** (`Stripe\Subscription`) to collect recurring donations. Stripe Billing is convenient but imposes rigid rules:

- Only `week`, `month`, `year` intervals are natively supported.
- Billing day, skip, pause, and amount changes must go through Stripe's lifecycle APIs.
- Retry/dunning logic is Stripe-controlled (Smart Retries), making it hard to build Malaysia/ASEAN-specific recovery flows.
- Each installment is represented as a Stripe Invoice, which adds a separate object lifecycle to reconcile.
- Platform fee collection differs between one-time (`application_fee_amount`) and recurring (`application_fee_percent`) flows.

Market leaders like **Fundraise Up** and **DonorBox** do **not** use Stripe Billing for recurring donations. They save the payment method, create a local "Recurring" record, and charge installments themselves using off-session `PaymentIntent`s. This gives them full control over schedules, retries, donor UX, and reporting.

The goal of this design is to move Ihsan to the same model for **new** recurring plans while avoiding a risky big-bang migration of existing Stripe Subscriptions.

---

## 2. Design Goals

| Goal | How measured |
|------|------------|
| New recurring plans are app-controlled | No new `stripe_subscription_id` created after deployment. |
| Every installment is a Donation record | Each charge creates a new `Donation` with `type = recurring`. |
| Consistent platform fee model | Use `application_fee_amount` on every `PaymentIntent`, same as one-time. |
| Retry/dunning is app-controlled | Failed charges follow an internal retry schedule and send dunning emails. |
| Donor portal still works | Pause, cancel, change amount, and update payment method modify local records or Stripe Customer payment methods. |
| Existing Stripe Subscriptions keep running | They continue charging via Stripe until cancelled or expired. |
| No payment loss from scheduler failure | Scheduler is idempotent and picks up missed `next_charge_at` dates. |
| Test coverage | Existing 213+ tests pass; new tests for charge engine, retry, and donor actions. |

---

## 3. High-Level Architecture

```
Donor submits monthly donation
  → CreatePaymentIntent (first charge, setup_future_usage=off_session)
  → payment_intent.succeeded webhook
  → CreateAppControlledRecurringPlan (NEW)
      → create local Subscription
      → attach payment method to Stripe Customer
      → sync DonorPaymentMethod
      → set next_charge_at

Daily scheduler (ChargeDueRecurringPlans)
  → find Subscriptions where next_charge_at <= now() and status = active
  → ChargeRecurringInstallment (NEW)
      → create off-session PaymentIntent
      → on success: create Donation, update next_charge_at, send receipt
      → on fail: update retry_count, schedule next retry, send dunning email

Donor portal actions
  → CancelLocalRecurringPlan
  → PauseLocalRecurringPlan
  → ResumeLocalRecurringPlan
  → ChangeRecurringAmount
  → UpdateRecurringPaymentMethod
```

---

## 4. Data Model Changes

### 4.1 `subscriptions` table

| Column | Type | Purpose |
|--------|------|---------|
| `stripe_subscription_id` | `text nullable` | Kept for legacy records; `NULL` for new app-controlled plans. |
| `stripe_price_id` | `text nullable` | Kept for legacy; `NULL` for new plans. |
| `donor_payment_method_id` | `foreign key → donor_payment_methods (nullable)` | Default payment method for app-controlled plans. |
| `next_charge_at` | `timestamp nullable` | Next scheduled charge date. |
| `last_charge_at` | `timestamp nullable` | Last successful charge attempt. |
| `last_charge_attempt_at` | `timestamp nullable` | Last charge attempt (success or fail). |
| `failed_installment_count` | `integer default 0` | Consecutive failed installments. |
| `retry_count` | already exists | Failed attempts for the current installment. |
| `interval` | `string` (expand enum) | `weekly`, `biweekly`, `monthly`, `bimonthly`, `quarterly`, `semiannual`, `yearly`. |
| `status` | `string` | `active`, `paused`, `cancelled`, `past_due`, `failed`, `completed`. |
| `pause_until` | `timestamp nullable` | Resume date for paused plans. |
| `cancel_at` | already exists | End date if donor sets a limit. |
| `max_plan_amount` | already exists | Stop after total amount reached. |
| `max_plan_installments` | already exists | Stop after N installments. |

### 4.2 New enum and helpers

- Expand `App\Enums\SubscriptionInterval` to support:
  - `Weekly`, `Biweekly`, `Monthly`, `Bimonthly`, `Quarterly`, `Semiannual`, `Yearly`.
- Add helper service `App\Services\SubscriptionSchedule` to calculate `next_charge_at` from a start date and interval, including day-of-month clamping (e.g., 31 Jan → 28 Feb).

### 4.3 `donor_payment_methods` table

No schema change, but behavior change:
- The **selected/used payment method** for a recurring plan must be stored as the plan's default.
- When a donor changes payment method, update `donor_payment_method_id` on the Subscription and set `is_default = true`.

---

## 5. Component Design

### 5.1 `CreateAppControlledRecurringPlan` (Action)

**Replaces `CreateRecurringSubscription` for new plans.**

Responsibilities:
1. Resolve or create Stripe Customer.
2. Attach the `PaymentMethod` used for the first donation to the customer.
3. Create or update `DonorPaymentMethod`.
4. Create local `Subscription` with:
   - `stripe_subscription_id = null`
   - `donor_payment_method_id`
   - `interval`
   - `next_charge_at` = calculated from now + interval
   - `payment_count = 1`
   - `status = active`
5. Link initial `Donation` to the Subscription.

**Signature:**
```php
public function create(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions = []): Subscription
```

### 5.2 `ChargeRecurringInstallment` (Action)

Responsibilities:
1. Load Subscription + Donor + Campaign + Organization.
2. Determine gross amount and donor-covered fee from subscription.
3. Create off-session `PaymentIntent`:
   - `customer` = donor's Stripe Customer ID
   - `payment_method` = subscription's saved payment method
   - `off_session = true`
   - `confirm = true`
   - `setup_future_usage = off_session`
   - `application_fee_amount` if org uses upfront fee collection
   - `metadata` = subscription_id, donor_id, campaign_id
   - `idempotency_key` = `{subscription_id}:{next_charge_at}:{retry_count}`
4. On `succeeded`:
   - Create new `Donation` record (type = recurring, linked to subscription).
   - Sync Stripe details (fees, payment method, risk data).
   - Increment `payment_count`.
   - Reset `retry_count` and `failed_installment_count`.
   - Update `last_charge_at`, `last_charge_attempt_at`, `next_charge_at`.
   - Update `collected_amount` on campaign.
   - Dispatch: receipt email, new-donation notification, conversion events.
5. On `requires_action`:
   - Mark installment as pending authentication.
   - Send email to donor with link to authenticate (or donor portal).
6. On failure:
   - Increment `retry_count`.
   - Update `last_charge_attempt_at`.
   - Schedule next retry via `ScheduleRetry`.
   - If retry limit reached, increment `failed_installment_count` and send dunning/failed email.

**Signature:**
```php
public function charge(Subscription $subscription): ChargeResult
```

### 5.3 `ScheduleRetry` (Service)

Calculates the next retry timestamp based on the retry policy.

Default policy (configurable per org later):
| Retry attempt | Delay after previous attempt |
|---------------|------------------------------|
| 1 | +1 day |
| 2 | +3 days |
| 3 | +7 days |
| 4+ | +7 days |

Also sets subscription status to `past_due` after first failure, and to `failed` after max retries or max failed installments.

### 5.4 `ChargeDueRecurringPlans` (Scheduled Command)

Command signature: `ihsan:charge-recurring-plans`

Responsibilities:
1. Query:
   ```sql
   SELECT * FROM subscriptions
   WHERE status = 'active'
     AND next_charge_at <= NOW()
     AND (paused_until IS NULL OR paused_until <= NOW())
     AND (cancel_at IS NULL OR cancel_at >= NOW())
   ```
2. For each subscription, dispatch `ChargeRecurringInstallment` to the queue.
3. Use chunking to avoid memory spikes.
4. Log run metrics.

Schedule in `routes/console.php`:
```php
Schedule::command('ihsan:charge-recurring-plans')->dailyAt('06:00')->timezone('Asia/Kuala_Lumpur');
```

### 5.5 `CreatePaymentIntent` (minor change)

For recurring donations, already sets `setup_future_usage = 'off_session'`. Keep this behavior. No further change needed unless we want to attach a `customer` at creation time for the first charge.

### 5.6 Webhook Changes

`ProcessStripeWebhook` changes:
- `handlePaymentIntentSucceeded`:
  - If donation is recurring and has no subscription, call `CreateAppControlledRecurringPlan`.
  - Else, treat as normal one-time or legacy recurring installment.
- `handleDonorInvoicePaid`:
  - Keep processing legacy Stripe Subscriptions that emit `invoice.paid`.
  - Skip if the subscription does not have a `stripe_subscription_id` (app-controlled plans never emit this).
- `handleInvoicePaymentFailed`:
  - Same: keep for legacy subscriptions only.
- `handleSubscriptionDeleted`:
  - Keep for legacy subscriptions.
- New `handlePaymentIntentRequiresAction` is **not** a Stripe webhook event; action-required state is handled synchronously or via job status.

### 5.7 Donor Portal Changes

Actions in `DonorSubscriptionController` must branch:
- If `$subscription->stripe_subscription_id` exists → use existing `ManageStripeSubscription` (legacy).
- If null → use new app-controlled managers.

New/updated managers (or direct controller logic):
- `CancelLocalRecurringPlan` → set `status = cancelled`, `cancelled_at = now()`, optionally cancel any pending Stripe Subscription.
- `PauseLocalRecurringPlan` → set `status = paused`, `paused_until = now()->addMonths($months)`, recalculate `next_charge_at`.
- `ResumeLocalRecurringPlan` → set `status = active`, clear `paused_until`, set `next_charge_at`.
- `ChangeRecurringAmount` → update local `amount`. Future installments use new amount. No proration.
- `UpdateRecurringPaymentMethod` → attach new Stripe PaymentMethod to customer, create/update `DonorPaymentMethod`, update `donor_payment_method_id` on Subscription.

---

## 6. Key Flows

### 6.1 New Recurring Donation

```
DonationForm::submit()
  → CreatePaymentIntent (with setup_future_usage=off_session)
  → donor pays with Stripe Elements
  → payment_intent.succeeded webhook fired
  → ProcessStripeWebhook::handlePaymentIntentSucceeded()
      → SyncDonationStripeDetails
      → Update donation status to succeeded
      → CreateAppControlledRecurringPlan::create()
          → Create/resolve Stripe Customer
          → Attach payment method to customer
          → Create DonorPaymentMethod
          → Create local Subscription with next_charge_at
      → SendNewSubscriptionNotification
      → SendDonorNewSubscriptionNotification
      → Update campaign collected_amount
```

### 6.2 Charge Due Installment

```
Scheduler runs ihsan:charge-recurring-plans
  → Queue job ChargeRecurringInstallment for each due subscription
  → CreatePaymentIntent (off-session, confirm=true)
  → Stripe attempts charge
  → IF succeeded
       Create new Donation
       SyncDonationStripeDetails
       Update campaign collected_amount
       next_charge_at = schedule->next()
       reset retry_count
       Send receipt + notifications + conversion events
  → IF requires_action
       Mark installment requires_action
       Send authentication email
  → IF failed
       retry_count++
       last_charge_attempt_at = now()
       next_charge_at = ScheduleRetry::next()
       IF retry_count > max
            failed_installment_count++
            IF failed_installment_count >= max_failed_installments
                 status = failed
            ELSE
                 status = past_due
            Send dunning / plan-failed email
```

### 6.3 Pause / Resume

```
Donor clicks Pause
  → Controller checks stripe_subscription_id
  → IF null
       status = paused
       paused_until = now + N months
       next_charge_at = max(next_charge_at, paused_until)
  → IF legacy → call ManageStripeSubscription::pause()

Donor clicks Resume
  → status = active
  → paused_until = null
  → next_charge_at = ScheduleService::nextAfter(now(), interval)
```

---

## 7. Retry & Dunning Rules

### Retry policy (default)

Stored in `config/services.php` or organization settings.

```php
'recurring' => [
    'retry_intervals_days' => [1, 3, 7, 7],
    'max_failed_installments' => 6,
    'mark_failed_after_max_retries' => true,
]
```

### Dunning emails

Reuse existing `DonorDunningNotification` and `FailedPaymentNotification` jobs.

- Retry 1 → "We couldn't process your donation" email.
- Retry 2 → "Please update your payment method" email.
- Retry 3 → "Final attempt" email.
- After max retries → "Plan failed" email.

---

## 8. Idempotency & Safety

- Every off-session `PaymentIntent` uses an idempotency key derived from `subscription_id:next_charge_at:retry_count`.
- Before creating a `Donation`, check if a donation with the same `stripe_payment_intent_id` already exists (defense against duplicate webhooks).
- The scheduler query uses `next_charge_at <= NOW()`, so if the scheduler missed a day it will still catch up the next run.
- Each charge is a separate queued job so one failure does not block others.

---

## 9. Legacy Stripe Subscriptions

- Existing records with `stripe_subscription_id` continue to work unchanged.
- `ProcessStripeWebhook` still handles `invoice.paid` and `invoice.payment_failed` for them.
- `ManageStripeSubscription` still used for donor portal actions on legacy records.
- When a legacy subscription is cancelled or expires, it is not migrated to app-controlled. New sign-ups only use the new model.
- Long-term (outside this design), run a migration script to convert active legacy subscriptions once they reach a natural break.

---

## 10. Testing Strategy

### Unit tests
- `SubscriptionScheduleTest` — next charge date calculation for all intervals and edge cases (Feb, leap year, month-end).
- `ScheduleRetryTest` — retry intervals and status transitions.
- `ChargeRecurringInstallmentTest` — mocks Stripe PaymentIntent with success, failure, requires_action.

### Feature tests
- `CreateAppControlledRecurringPlanTest` — new recurring donation creates local Subscription with no `stripe_subscription_id`.
- `ChargeDueRecurringPlansCommandTest` — scheduler dispatches jobs for due subscriptions only.
- `RecurringInstallmentSuccessTest` — successful charge creates new Donation and updates campaign total.
- `RecurringInstallmentFailureTest` — failed charge updates retry count, status, and sends dunning.
- `DonorPortalRecurringPauseResumeTest` — pause/resume for app-controlled plan.
- `DonorPortalChangeAmountTest` — amount change updates future installments.

### Existing tests
- Run full test suite before and after implementation.
- Ensure no new `stripe_subscription_id` is created in recurring feature tests unless explicitly legacy.

---

## 11. Implementation Phases

### Phase 1 — Schema & enum (1–2 days)
1. Migration: add `donor_payment_method_id`, `next_charge_at`, `last_charge_at`, `last_charge_attempt_at`, `failed_installment_count` to `subscriptions`.
2. Expand `SubscriptionInterval` enum.
3. Create `SubscriptionSchedule` service.
4. Add `ihsan:charge-recurring-plans` command skeleton.

### Phase 2 — Create app-controlled plan (2–3 days)
1. Implement `CreateAppControlledRecurringPlan`.
2. Refactor `DonationForm::confirmPayment()` to use it.
3. Refactor webhook handler `handlePaymentIntentSucceeded` to call new action for new recurring plans.
4. Update `CreatePaymentIntent` to attach `customer` during first recurring donation (so payment method can be reused).

### Phase 3 — Charge engine (3–4 days)
1. Implement `ChargeRecurringInstallment` job/action.
2. Implement `ScheduleRetry` service.
3. Implement `ihsan:charge-recurring-plans` scheduled command.
4. Wire dunning emails.
5. Create new `Donation` records on success.

### Phase 4 — Donor portal branching (2–3 days)
1. Update `DonorSubscriptionController` to branch by `stripe_subscription_id`.
2. Implement local cancel/pause/resume/change-amount/update-payment-method.
3. Update donor portal views to show both legacy and app-controlled statuses.

### Phase 5 — Tests & polish (2–3 days)
1. Write unit/feature tests for all new components.
2. Run full test suite.
3. Add scheduler monitoring/logging.
4. Update relevant admin views/filters.

**Estimated total: 2–3 weeks for one developer.**

---

## 12. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Scheduler failure causes missed charges | Daily scheduler + idempotent retry; monitoring via logs/queue size. |
| Duplicated charges | Idempotency keys on PaymentIntent + unique index on `donations.stripe_payment_intent_id`. |
| Off-session SCA decline | Handle `requires_action`, send donor authentication link. |
| Payment method detached/invalid | Catch error, mark plan `past_due`, prompt donor to update card. |
| Existing Stripe Subscriptions double-processed | `invoice.paid` handler skips records with `stripe_subscription_id` already migrated (not in scope). |
| Large migration/refactor risk | Hybrid approach keeps legacy untouched; only new plans use new engine. |

---

## 13. Open Questions (for review)

1. Should the first donation be charged immediately, or should it be tokenized and charged on the schedule start date? (Fundraise Up immediate; Virtual Terminal supports future start.)
2. Should pausing push the next charge date, or skip the missed installments? (Recommendation: push.)
3. Should we support billing-day selection in this phase, or keep donor's signup day as billing day?
4. Should `failed_installment_count` reset after a successful charge, or accumulate until plan fails? (Recommendation: reset on success.)
5. Do we need to migrate active Virtual Terminal Stripe Subscriptions to app-controlled, or let them run out?

---

## 14. Files to Create / Modify

### New files
- `app/Actions/Stripe/CreateAppControlledRecurringPlan.php`
- `app/Actions/Stripe/ChargeRecurringInstallment.php`
- `app/Services/SubscriptionSchedule.php`
- `app/Services/ScheduleRetry.php`
- `app/Console/Commands/ChargeDueRecurringPlans.php`
- `app/Data/ChargeResult.php` (or array shape)
- Tests: `tests/Unit/SubscriptionScheduleTest.php`, `tests/Feature/CreateAppControlledRecurringPlanTest.php`, `tests/Feature/ChargeDueRecurringPlansTest.php`, etc.

### Modified files
- `app/Enums/SubscriptionInterval.php`
- `app/Livewire/DonationForm.php`
- `app/Jobs/ProcessStripeWebhook.php`
- `app/Http/Controllers/DonorSubscriptionController.php`
- `app/Actions/Stripe/CreatePaymentIntent.php`
- `app/Actions/Stripe/CreateRecurringSubscription.php` (deprecate or restrict to legacy)
- `routes/console.php`
- `app/Models/Subscription.php` (casts, relations)

### Migration
- `database/migrations/2026_06_26_000000_add_app_controlled_columns_to_subscriptions.php`

---

*Ready for review. Once approved, the next step is to invoke the writing-plans skill and produce the implementation plan.*
