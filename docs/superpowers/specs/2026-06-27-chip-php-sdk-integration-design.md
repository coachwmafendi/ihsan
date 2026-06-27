# CHIP PHP SDK Integration Design

## Goal

Add CHIP (CHIP Asia / CHIP Collect) as an alternative payment gateway to Stripe for the Ihsan donation platform, allowing each campaign to choose between Stripe and CHIP.

## Context

The application currently uses Stripe as its only payment provider, with support for:

- One-time donations
- Stripe-managed and app-controlled recurring donations
- Stripe Connect for organization-level accounts
- Refunds, donor fee cover, processing fees, and monthly invoicing

The codebase uses single-purpose Action classes under `app/Actions/Stripe/*`. All gateway-specific logic is currently hard-coded for Stripe, with a `PaymentGateway` enum that only contains `Stripe`.

## Design Approach

Use **parallel Action classes** under `app/Actions/Chip/*` that mirror the existing Stripe Actions. A small factory/service layer decides which gateway action to invoke based on `campaign->payment_gateway`. This keeps Stripe code untouched and follows the existing architectural pattern.

## Scope

### In Scope (MVP)

- One-time donations via CHIP
- Recurring donations via CHIP (card-only)
- Refunds via CHIP
- CHIP organization settings (Brand ID, API Key, payment method whitelist)
- Campaign-level gateway selection (Stripe or CHIP)
- Iframe modal checkout
- CHIP webhook verification and fulfillment
- Embed widget support for CHIP (button, form, popup)
- MYR currency focus

### Out of Scope

- CHIP virtual terminal (phase 2)
- Multi-currency CHIP donations
- Detailed per-method fee sync from CHIP API
- Customer-side split payments

## Data Model Changes

### `PaymentGateway` Enum

Add a new value:

```php
enum PaymentGateway: string
{
    case Stripe = 'stripe';
    case Chip = 'chip';
}
```

### `Organization` Model

Add new nullable columns (or dedicated settings):

- `chip_brand_id` — CHIP Brand ID
- `chip_api_key` — CHIP API Key
- `chip_onboarded` — accessor, true when both values are present

Payment method whitelist stored in `settings` JSON:

```json
{
  "chip_payment_methods": ["card", "fpx"]
}
```

Default: `["card"]`

### `Campaign` Model

No schema change. The existing `payment_gateway` column (cast to `PaymentGateway` enum) will support `chip`.

### `Donation` Model

Add CHIP-specific nullable fields:

- `chip_purchase_id`
- `chip_checkout_url`

Existing Stripe fields remain unchanged.

### `Subscription` Model

Add CHIP-specific fields for recurring token management:

- `chip_recurring_token`
- `payment_gateway` already exists and will support `chip`

## CHIP Credentials & Onboarding

- Each organization manages its own CHIP Brand ID and API Key.
- Credentials are entered through a new CHIP settings page in the organization dashboard.
- A "Test Connection" button calls a lightweight CHIP API endpoint to validate credentials.
- `chip_onboarded` is derived from whether both Brand ID and API Key are present.

## Campaign Gateway Selection

- Campaign form allows selecting `stripe` or `chip` from the `payment_gateway` dropdown.
- If an organization has not configured CHIP credentials, the `chip` option is disabled with explanatory text.
- Validation prevents saving a campaign with `payment_gateway = chip` if the organization is not CHIP-onboarded.

## Payment Method Whitelist

- Organizations can enable `card`, `fpx`, or both for CHIP.
- Default is `["card"]`.
- Card is required for recurring donations.
- If only `fpx` is enabled, recurring donation options are hidden/disabled.

## One-Time Donation Flow

1. Donor submits donation form.
2. `DonationForm` Livewire component creates/updates `Donor` and creates a pending `Donation`.
3. If `campaign->payment_gateway === PaymentGateway::Chip`:
   - Call `CreatePurchase::create($donation)`.
   - Build CHIP purchase via `PurchaseBuilder`:
     - `brand_id` from organization
     - `currency` (MYR)
     - `client_email`, `client_full_name`
     - product with campaign name and amount
     - `success_callback` pointing to CHIP webhook route
     - `success_redirect` and `failure_redirect` pointing to iframe callback pages
   - Save `chip_purchase_id` and `chip_checkout_url` on the donation.
   - Emit `openChipCheckout` event with the checkout URL.
4. Frontend opens a Flux/Alpine modal containing an iframe with `src="checkout_url"`.
5. Donor completes payment within the iframe.
6. CHIP redirects the iframe to the success/failure callback route on our domain.
7. The callback page sends a `postMessage` event to the parent window.
8. Parent window closes the modal and calls `$wire.confirmChipPayment()`.
9. Backend verifies the purchase status via CHIP API (forgery protection) and marks the donation as succeeded.
10. Webhook callback from CHIP also triggers backend fulfillment as a safety net.

### Fallback

If CHIP checkout cannot be embedded in an iframe due to `X-Frame-Options` or other restrictions, the frontend falls back to full-page redirect.

## Recurring Donation Flow

- Recurring donations via CHIP require the `card` payment method.
- When creating the initial CHIP purchase, request recurring token consent.
- On payment success, store the recurring token in the `Subscription` model.
- Create a local `Subscription` row with:
  - `payment_gateway = chip`
  - `chip_recurring_token`
  - `stripe_subscription_id = null`
- Future installments are charged via `ChargeRecurringInstallment` using `$chip->purchases->charge()`.
- Each installment creates a new `Donation` record.

## Refund Flow

- Admin refund action checks `donation->payment_gateway`.
- For CHIP donations, call `RefundDonation::handle($donation)`.
- Use `$chip->purchases->refund($purchaseId)` for full refunds or `$chip->purchases->refund($purchaseId, $amount)` for partial refunds.
- On success, mark donation as `refunded`, decrement campaign total, and dispatch refund notification.

## CHIP Action Classes

Create the following parallel Action classes under `app/Actions/Chip/`:

| Class | Responsibility |
|-------|----------------|
| `CreatePurchase.php` | Create a CHIP purchase and save checkout URL on the donation |
| `SyncDonationDetails.php` | Fetch purchase details from CHIP and update donation/fee records |
| `CreateRecurringPlan.php` | Create a local subscription with CHIP recurring token |
| `ChargeRecurringInstallment.php` | Charge the next installment using recurring token |
| `RefundDonation.php` | Process full or partial refund via CHIP |
| `VerifyWebhook.php` | Verify CHIP webhook signature using public key |

## Webhook Handling

- Route: `POST /chip/webhook`
- Controller: `ChipWebhookController`
- Job: `ProcessChipWebhook`
- Verify signature using `ChipApi::verify($payload, $signature, $publicKey)`.
- Public key is fetched from CHIP API and cached.
- Use `WebhookLog` for idempotency (event ID unique per gateway).
- Add a `gateway` column or distinguish CHIP events from Stripe events.
- Handle CHIP purchase status events generically by re-fetching purchase details from CHIP and updating local records.

## Fees & Processing Fees

### Fee Configuration

- Card: percentage-based (default 2.5%, configurable globally and per organization)
- FPX: percentage or fixed amount (configurable per organization)

### Fee Flow

- CHIP uses per-organization accounts.
- All funds (donation + donor-covered fee) go directly to the organization's CHIP account.
- Platform fee is tracked as a `ProcessingFee` record with status `pending`.
- Fee is collected from the organization via the existing monthly invoice flow.

### Donor Fee Cover

- Donor fee cover is enabled by default for CHIP.
- Frontend recalculates `fee_cover_amount` based on selected method (card vs FPX).
- Total purchase amount = donation amount + fee cover amount.

## Notifications

- Reuse existing notification jobs where possible.
- Ensure `SendNewDonationNotification`, `SendRefundNotification`, `SendNewSubscriptionNotification`, and `SendSubscriptionCancelledNotification` are triggered for CHIP donations.

## Embed Widget

- `widget.js` reads `campaign->payment_gateway`.
- For CHIP elements:
  - Button/Popup: open iframe modal on click
  - Form: render donation form normally and submit to iframe modal
- Embed code remains a single `widget.js` with `data-type` attribute.

## Settings UI

- Add a CHIP section to the organization payment settings.
- Fields:
  - Chip Brand ID
  - Chip API Key
  - Payment method whitelist (card, fpx)
  - Test Connection button
- Show CHIP onboarding status (connected / not connected).

## Testing

### Unit Tests

- `CreateChipPurchaseTest` — verify purchase payload and checkout URL storage
- `SyncChipDonationDetailsTest` — verify donation and fee updates
- `VerifyChipWebhookTest` — verify signature validation

### Feature Tests

- `ChipOneTimeDonationTest` — full iframe modal flow with mocked CHIP API
- `ChipWebhookTest` — webhook verification and fulfillment
- `ChipRefundTest` — refund action and status updates
- `ChipRecurringTest` — recurring token subscription and installment charging
- `CampaignGatewaySelectionTest` — validation of gateway selection against org credentials
- `ChipSettingsTest` — saving credentials and payment method whitelist

## Configuration

Add to `config/services.php`:

```php
'chip' => [
    'processing_fee_percent' => env('CHIP_PROCESSING_FEE_PERCENT', 2.5),
    'fpx_fee_type' => env('CHIP_FPX_FEE_TYPE', 'fixed'), // 'fixed' or 'percent'
    'fpx_fee_amount' => env('CHIP_FPX_FEE_AMOUNT', 150), // in smallest currency unit
],
```

## Risks & Considerations

- **Iframe embedding restrictions**: CHIP checkout may block iframe rendering. Fallback to redirect must be implemented.
- **Webhook event shapes**: CHIP webhook event names may differ from assumptions. Implementation should inspect purchase status rather than rely on exact event names.
- **Recurring token availability**: Recurring token behavior depends on CHIP merchant settings. Test thoroughly in sandbox.
- **Currency**: MVP focuses on MYR. Multi-currency support is out of scope.
- **Fee accuracy**: CHIP actual fees depend on merchant agreement. Configured fee rates are platform-defined.

## Dependencies

- `chip/chip-sdk-php:^2.0` via VCS repository

## Installation

```bash
composer config repositories.chip-sdk vcs https://github.com/CHIPAsia/chip-php-sdk.git
composer require chip/chip-sdk-php:^2.0
```

## Next Steps

After this design is approved, create an implementation plan with bite-sized tasks covering:

1. Install CHIP SDK and add configuration
2. Update enums, models, and migrations
3. Build CHIP Action classes
4. Implement CHIP checkout and iframe modal
5. Implement CHIP webhook handling
6. Add CHIP refund and recurring support
7. Update settings UI and campaign form
8. Update embed widget for CHIP
9. Write tests
10. Run full test suite and fix regressions
