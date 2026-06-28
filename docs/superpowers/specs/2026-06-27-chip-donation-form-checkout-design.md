# CHIP donation-form checkout integration

## Context
CHIP is being added as a selectable alternative payment gateway alongside Stripe.
Campaigns can now choose a `payment_gateway` (`stripe` or `chip`).
The existing `DonationForm` / `donationStep` Alpine flow is hard-coded to Stripe Payment Element.
This design wires up CHIP so that donors can complete payment through a CHIP iframe modal.

## Goals
1. Use `CreatePurchase` for CHIP campaigns.
2. Pass the donor through a CHIP checkout iframe modal.
3. Handle recurring (monthly) CHIP donations.
4. Finalize the donation safely and idempotently, whether triggered by callback postMessage or webhook.

## Decisions
- **Checkout UX on public pages:** modal iframe (confirmed by user).
- **Recurring CHIP donations:** enabled now (confirmed by user).
- **Gateway branching approach:** branch inside the existing `DonationForm` + Alpine component. This avoids a large refactor of the stable Stripe path.
- **Finalization idempotency:** add a nullable `finalized_at` timestamp to `donations`. Backend finalization checks this column before it increments campaign totals or dispatches notifications.
- **Dual fulfillment:** both the callback page and the webhook job call the same `FinalizeDonation` action, so a donation can be completed either by the browser returning from CHIP or by a delayed webhook.

## Backend changes

### `App\Actions\Chip\CreatePurchase::create(Donation $donation): string`
Already exists. Extend the builder call to include:
- `forceRecurring(true)` when `$donation->type === DonationType::Recurring`.
- `paymentMethodWhitelist($organization->chipPaymentMethods())`.
- `cancelRedirect(route('chip.callback', ['donation' => $donation->public_id, 'status' => 'cancelled']))`.

Continue to return `$result->checkout_url` and persist `chip_purchase_id` / `chip_checkout_url`.

### `App\Actions\Chip\SyncDonationDetails::sync(Donation $donation): void`
Already exists. Add, on status `Succeeded`:
- If `$purchase->recurring_token` is present and the donation is recurring, update `donations.chip_recurring_token`.

### `App\Actions\Chip\FinalizeDonation::finalize(Donation $donation): void` (new)
Responsibilities:
1. Load `campaign.organization`.
2. Call `SyncDonationDetails::sync($donation)` to get the latest CHIP status.
3. If the purchase is not `Succeeded`, raise a clear exception (or return, depending on caller).
4. In a DB transaction, lock the donation row:
   - If `finalized_at` is already set, return immediately (idempotent).
   - Update donation status (already done by sync, but keep explicit).
   - Increment campaign `collected_amount` by `base_amount ?? gross_amount`.
   - Set `finalized_at = now()`.
5. For **recurring** donations, call `CreateRecurringPlan->create($donation, $donation->chip_recurring_token)` if it does not yet have a `subscription_id`.
6. Dispatch notifications:
   - Recurring: `SendNewSubscriptionNotification`, `SendDonorNewSubscriptionNotification`.
   - One-time: `SendDonationReceipt`, `SendNewDonationNotification`, `SendLargeDonationNotification`, and conversion events (`SendMetaConversionEvent`, `SendLinkedInConversionEvent`, `SendXAdsConversionEvent`, `SendSnapchatConversionEvent`).

### `DonationForm::submit(): string`
- Determine campaign gateway: `$campaign->payment_gateway?->value === 'chip'`.
- If **Stripe**, keep current flow (call `CreatePaymentIntent`, return `client_secret`).
- If **CHIP**:
  - Validate the organization is CHIP onboarded; if not, mark donation failed and throw.
  - Call `CreatePurchase->create($donation)` and return the checkout URL string.

### New route + controller
```
POST /chip/finalize/{donation:public_id}
```
- `App\Http\Controllers\ChipFinalizeController`.
- Unauthenticated public endpoint.
- Calls `FinalizeDonation->finalize($donation)`.
- Returns JSON `{ finalized: true }` on success, or appropriate 4xx/5xx with message on error.

### `App\Jobs\ProcessChipWebhook`
- After locating the donation, call `FinalizeDonation->finalize($donation)`.
- Catch finalization exceptions and report them; do not fail the job permanently for transient sync issues (one retry is acceptable).

### Database migration
Add nullable `finalized_at` timestamp to `donations`:
```php
$table->timestamp('finalized_at')->nullable()->after('status');
```

## Frontend changes

### `resources/views/partials/donation-step.blade.php`
Add a new init parameter for `paymentGateway` (`'stripe'` or `'chip'`).

In `handleSubmit()`:
- After `clientSecret = await this.$wire.submit()`:
  - If the response starts with `http`, treat it as a CHIP checkout URL.
  - Otherwise continue with the existing Stripe Payment Element flow.

For CHIP:
- Store the URL.
- If `isEmbed` or `isPopup`: redirect the current window/iframe to the checkout URL (`window.location.href = checkoutUrl`).
- If a public page: open an inline modal iframe with the checkout URL (reuse the same visual style used by `widget.js`).

Add a window message listener for:
- `chip:payment:success`:
  - Close the CHIP modal if open.
  - Keep/show `processing` state.
  - Call `this.$wire.confirmChipPayment(this.donationPublicId)` (or the finalize endpoint if the component was destroyed because of a redirect).
  - Track purchase and transition to `currentStep = 'success'`.
- `chip:payment:failure`:
  - Close modal, set `processing = false`, set `currentStep = 'error'`, show a friendly error.
- `chip:payment:cancel` (optional, same as failure).

### `DonationForm` new public method: `confirmChipPayment(string $donationPublicId): void`
- Look up the donation by `public_id` and verify it belongs to the current campaign/element (optional sanity check).
- Call `FinalizeDonation->finalize($donation)`.
- Refresh campaign totals (`syncCampaignTotals`) so the UI shows updated collected amount.
- `#[Renderless]`, with `skipRender()` if needed.

### `resources/views/chip/callback.blade.php`
After page load:
1. Call `POST /chip/finalize/{donation_public_id}` (fetch). This is best-effort.
2. After a short delay, postMessage to parent:
   - `chip:payment:success`, `chip:payment:failure`, or `chip:payment:cancel` depending on URL `status`.
3. Include `donationId: donation_public_id`.

### `resources/js/widget.js`
Listen for `chip:payment:success`, `chip:payment:failure`, and `chip:payment:cancel` in the existing modal message handler:
- On success: close overlay (and optionally reload the host page after a short delay so the donor sees a clean completed state).
- On failure/cancel: close overlay.

## Testing
1. `tests/Feature/ChipDonationFormTest.php`:
   - CHIP campaign submit returns a URL.
   - Non-onboarded CHIP organization returns an error and donation marked failed.
   - One-time and recurring donations create the correct purchase shape.
2. `tests/Feature/ChipFinalizeTest.php`:
   - Endpoint finalizes a succeeded purchase.
   - Recurring finalization creates a `Subscription` and links it.
   - Calling finalize twice is idempotent (`collected_amount` not double-incremented).
   - Webhook job calls finalize.
3. `tests/Feature/ChipCallbackTest.php` (extend):
   - Callback page contains the finalize fetch + postMessage.

## Out of scope
- Virtual terminal CHIP support.
- Partial refunds for CHIP.
- A generic gateway strategy abstraction (kept as conditional branches to minimize Stripe churn).
