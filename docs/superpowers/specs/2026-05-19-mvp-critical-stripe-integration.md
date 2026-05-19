# MVP Critical: Stripe Integration & Payment Flow

## 1. Overview

Complete the remaining critical path for Ihsan MVP: real payment processing via Stripe, NGO onboarding via Stripe Connect Express, email receipts, donor portal magic link, and async job processing.

## 2. Prerequisites Check

- [x] Stripe account keys ready (STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET, STRIPE_CONNECT_CLIENT_ID)
- [ ] Install `stripe/stripe-php` package
- [ ] Add config to `config/services.php`
- [ ] Add env vars to `.env`

## 3. Payment Flow (Stripe Elements + PaymentIntents)

### 3.1 Flow Diagram

```
Donor fills form → Livewire submit → POST /stripe/payment-intent
                                        → Stripe API: PaymentIntent.create
                                        → Return { client_secret, donation_id }
                                  → Stripe.js Elements: confirmCardPayment(client_secret)
                                  → Success → Livewire: mark donation succeeded
                                  → Webhook backup: payment_intent.succeeded
```

### 3.2 Server-Side

**Route:** `POST /stripe/payment-intent` → `StripePaymentIntentController`
- Create/update Donation record with `stripe_payment_intent_id`
- Create Stripe PaymentIntent with amount, currency (myr), metadata (donation_id, campaign_id, organization_id)
- For connected accounts: use `application_fee_amount` for platform fee
- Return `{ client_secret, donation_id }`

### 3.3 Client-Side (DonationForm Livewire)

- Load Stripe.js via `@stripe/stripe-js` (npm) or direct script
- Mount Stripe Elements (card number, expiry, CVC) in the donation form
- On submit: prevent default, call server for PaymentIntent, confirm with Elements
- Handle errors (card declined, insufficient funds) inline
- On success: redirect to thank-you page

### 3.4 Webhook Handling

**Controller:** `StripeWebhookController` (no CSRF, no auth — verified via signature)

Events handled:
| Event | Action |
|---|---|
| `payment_intent.succeeded` | Update Donation → succeeded, update Campaign collected_amount |
| `payment_intent.payment_failed` | Update Donation → failed |
| `invoice.paid` | Handle recurring donation billing |
| `invoice.payment_failed` | Mark subscription past_due, trigger dunning |
| `customer.subscription.deleted` | Mark subscription cancelled |
| `customer.subscription.updated` | Sync subscription status |
| `account.updated` | Mark organization as stripe_onboarded if charges_enabled |

**Async:** All webhooks dispatched to queue job `ProcessStripeWebhook` for reliability.

## 4. Stripe Connect Express (NGO Onboarding)

### 4.1 Account Creation

When Organization status changes to `Active` (via admin approval):
- Create Stripe Connect Express account via API
- Save `stripe_account_id`
- Generate account link for onboarding

### 4.2 Onboarding Flow

- NGO admin sees **"Sambung Stripe"** button in panel (visible if `stripe_account_id` set but `stripe_onboarded` is false)
- Click → redirect to Stripe hosted onboarding
- Webhook `account.updated` → set `stripe_onboarded = true` when `charges_enabled` is true

## 5. Email Notifications

### 5.1 Mailables

| Mailable | Trigger | To |
|---|---|---|
| `DonationReceipt` | Payment successful | Donor email |
| `SubscriptionReceipt` | Recurring payment successful | Donor email |
| `PaymentFailed` | invoice.payment_failed | Donor email |

### 5.2 Config

- Dev: log driver (already setup via Mailtrap/Log)
- Mailable structure: organization branding, donation details, magic link to portal

## 6. Donor Portal (Magic Link)

### 6.1 Magic Link Auth

- Donation receipt email includes magic link: `/donor/login?token={magic_token}`
- Validate token from `donors` table
- Set session, redirect to donor dashboard
- Token expires after configurable duration (default 24h)

### 6.2 Donor Pages

| Route | Description |
|---|---|
| `GET /donor/donations` | Donation history |
| `GET /donor/subscriptions` | Active subscriptions |
| `POST /donor/subscriptions/{id}/cancel` | Cancel subscription |

## 7. Queue & Async

### 7.1 Jobs

| Job | Purpose |
|---|---|
| `ProcessStripeWebhook` | Handle incoming webhook events |
| `SendDonationReceipt` | Email receipt after successful payment |
| `SendSubscriptionReceipt` | Email receipt for recurring payment |

### 7.2 Queue

- Default `sync` queue for development
- Ready for `database`/`redis` queue in production

## 8. Implementation Order

1. Install `stripe/stripe-php` + config
2. Create `ProcessStripeWebhook` job
3. Build `StripePaymentIntentController` + route
4. Update `DonationForm` Livewire with Stripe Elements
5. Build `StripeWebhookController` + routes
6. Stripe Connect Express onboarding flow
7. Mailables (DonationReceipt, etc.)
8. Donor portal magic link + pages
9. Wire up webhook events to mark donations/subscriptions
