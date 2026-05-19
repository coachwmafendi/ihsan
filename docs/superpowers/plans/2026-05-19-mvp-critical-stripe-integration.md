# MVP Critical: Stripe Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete payment processing via Stripe (Elements + PaymentIntents), Stripe Connect Express onboarding, email receipts, donor portal with magic link, and async job processing.

**Architecture:** PaymentIntent created server-side via new API controller → Stripe Elements confirms on frontend → webhook handles async updates → queue jobs for notifications. Stripe Connect Express accounts created on org approval with onboarding link in NGO panel.

**Tech Stack:** stripe/stripe-php, @stripe/stripe-js, Laravel queue, Laravel mail, Livewire + Alpine.js

---

## File Structure

### New Files to Create:
- `app/Http/Controllers/StripePaymentIntentController.php` — Create PaymentIntent, return client_secret
- `app/Http/Controllers/StripeWebhookController.php` — Handle incoming Stripe webhooks
- `app/Http/Controllers/DonorAuthController.php` — Magic link login
- `app/Http/Controllers/DonorPortalController.php` — Donor dashboard pages
- `app/Jobs/ProcessStripeWebhook.php` — Async webhook processor
- `app/Jobs/SendDonationReceipt.php` — Email receipt job
- `app/Mail/DonationReceipt.php` — Donation receipt mailable
- `app/Mail/MagicLink.php` — Magic link email
- `app/Actions/Stripe/CreateConnectAccount.php` — Create Stripe Connect Express account
- `app/Actions/Stripe/CreatePaymentIntent.php` — Build & create PaymentIntent
- `resources/views/donor/donations.blade.php` — Donor donation history
- `resources/views/donor/subscriptions.blade.php` — Donor subscription list
- `resources/views/donor/layout.blade.php` — Donor portal layout
- `resources/views/emails/donation-receipt.blade.php` — Receipt email template
- `resources/views/emails/magic-link.blade.php` — Magic link email template
- `tests/Feature/StripePaymentIntentTest.php`
- `tests/Feature/StripeWebhookTest.php`
- `tests/Feature/DonorPortalTest.php`
- `tests/Feature/Mail/DonationReceiptTest.php`

### Files to Modify:
- `composer.json` — add `stripe/stripe-php`
- `config/services.php` — add Stripe config
- `.env` — add Stripe env vars (user provides)
- `.env.example` — add Stripe env placeholders
- `routes/web.php` — add Stripe payment intent route, webhook route, donor routes
- `app/Livewire/DonationForm.php` — add Stripe payment flow method
- `resources/views/livewire/donation-form.blade.php` — add Stripe Elements card form + Alpine.js
- `resources/views/layouts/donation.blade.php` — load Stripe.js
- `app/Models/Donation.php` — add helper methods
- `app/Models/Donor.php` — add magic link methods
- `app/Filament/Resources/Organizations/Pages/EditOrganization.php` — add Stripe Connect account creation on approve
- `app/Filament/App/Pages/Dashboard.php` — add "Sambung Stripe" button
- `tests/Feature/HostedDonationFormTest.php` — update test expectations
- `vite.config.js` — add Stripe.js to external (if needed)

---

### Task 1: Install Stripe SDK & Configure

**Files:**
- Modify: `composer.json`
- Modify: `config/services.php`
- Modify: `.env.example`
- Create: (none)

- [ ] **Step 1: Add stripstripe-php to composer.json**

Run: `composer require stripe/stripe-php`

- [ ] **Step 2: Add Stripe config to config/services.php**

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
],
```

- [ ] **Step 3: Add Stripe env vars to .env.example**

```
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_CONNECT_CLIENT_ID=
```

- [ ] **Step 4: Install @stripe/stripe-js npm package**

Run: `npm install @stripe/stripe-js`

- [ ] **Step 5: Commit**

```bash
git add composer.json config/services.php .env.example package.json
git commit -m "feat: install Stripe SDK and configure services"
```

---

### Task 2: Create PaymentIntent Action & Controller

**Files:**
- Create: `app/Actions/Stripe/CreatePaymentIntent.php`
- Create: `app/Http/Controllers/StripePaymentIntentController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create CreatePaymentIntent action**

```php
<?php

namespace App\Actions\Stripe;

use App\Models\Donation;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class CreatePaymentIntent
{
    public function create(Donation $donation): PaymentIntent
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $organization = $donation->campaign->organization;

        $params = [
            'amount' => (int) ((float) $donation->gross_amount * 100),
            'currency' => strtolower($donation->currency),
            'metadata' => [
                'donation_id' => (string) $donation->getKey(),
                'campaign_id' => (string) $donation->campaign_id,
                'organization_id' => (string) $organization->getKey(),
            ],
        ];

        if ($organization->stripe_account_id && $organization->stripe_onboarded) {
            $params['application_fee_amount'] = (int) ((float) $donation->gross_amount * 0.05 * 100);
            $params['transfer_data'] = [
                'destination' => $organization->stripe_account_id,
            ];
        }

        return PaymentIntent::create($params);
    }
}
```

- [ ] **Step 2: Create StripePaymentIntentController**

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StripePaymentIntentController extends Controller
{
    public function __invoke(Request $request, CreatePaymentIntent $createPaymentIntent): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'donor_name' => ['required', 'string', 'max:120'],
            'donor_email' => ['required', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:40'],
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'currency' => ['required', 'string', 'size:3'],
            'type' => ['required', 'in:one_time,monthly'],
        ]);

        $campaign = Campaign::query()->findOrFail($validated['campaign_id']);

        $donor = Donor::query()->updateOrCreate(
            ['email' => Str::lower($validated['donor_email'])],
            [
                'name' => $validated['donor_name'],
                'phone' => $validated['donor_phone'] ?? null,
            ],
        );

        $donation = Donation::query()->create([
            'campaign_id' => $campaign->getKey(),
            'donor_id' => $donor->getKey(),
            'gross_amount' => $validated['amount'],
            'stripe_fee' => 0,
            'platform_fee' => 0,
            'net_amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'status' => DonationStatus::Pending,
            'type' => $validated['type'] === 'monthly' ? \App\Enums\DonationType::Recurring : \App\Enums\DonationType::OneTime,
        ]);

        try {
            $paymentIntent = $createPaymentIntent->create($donation);

            $donation->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'donation_id' => $donation->getKey(),
            ]);
        } catch (\Exception $e) {
            $donation->update(['status' => DonationStatus::Failed]);

            throw ValidationException::withMessages([
                'payment' => ['Payment could not be processed. Please try again.'],
            ]);
        }
    }
}
```

- [ ] **Step 3: Add route to routes/web.php**

```php
use App\Http\Controllers\StripePaymentIntentController;

// Public Stripe API
Route::post('/stripe/payment-intent', StripePaymentIntentController::class)
    ->middleware('throttle:10,1')
    ->name('stripe.payment-intent');
```

- [ ] **Step 4: Commit**

```bash
git add app/Actions/Stripe/CreatePaymentIntent.php app/Http/Controllers/StripePaymentIntentController.php routes/web.php
git commit -m "feat: add PaymentIntent creation endpoint"
```

---

### Task 3: Update DonationForm Livewire with Stripe Elements

**Files:**
- Modify: `app/Livewire/DonationForm.php`
- Modify: `resources/views/livewire/donation-form.blade.php`
- Modify: `resources/views/layouts/donation.blade.php`

- [ ] **Step 1: Add Stripe publishable key to donation layout**

```blade
{{-- resources/views/layouts/donation.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script src="https://js.stripe.com/v3/"></script>
        @livewireStyles
    </head>
    <body class="min-h-screen bg-[#eef1f6] text-slate-950 antialiased">
        {{ $slot }}

        @livewireScripts
        @env('local')
            <script>
                window.stripePublishableKey = 'pk_test_xxxxxxxx';
            </script>
            @endenv
    </body>
</html>
```

- [ ] **Step 2: Update submit method in DonationForm to create PaymentIntent**

Add a new method `processPayment` and modify the submit flow:

```php
public string $stripeClientSecret = '';

public function submit(): void
{
    $validated = $this->validate();
    $email = str($validated['email'])->lower()->toString();

    $donor = Donor::query()->updateOrCreate(
        ['email' => $email],
        [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ],
    );

    $donation = Donation::query()->create([
        'campaign_id' => $this->element->campaign_id,
        'donor_id' => $donor->getKey(),
        'gross_amount' => $validated['amount'],
        'stripe_fee' => 0,
        'platform_fee' => 0,
        'net_amount' => $validated['amount'],
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => $validated['frequency'] === 'monthly' ? DonationType::Recurring : DonationType::OneTime,
        'donor_message' => filled($validated['comment'] ?? null) ? $validated['comment'] : null,
        'is_anonymous' => false,
        'utm_params' => [
            'element_id' => $this->element->getKey(),
            'element_token' => $this->element->token,
            'frequency' => $validated['frequency'],
            'dedicate' => (bool) ($validated['dedicate'] ?? false),
        ],
    ]);

    try {
        $paymentIntent = app(\App\Actions\Stripe\CreatePaymentIntent::class)->create($donation);
        $donation->update(['stripe_payment_intent_id' => $paymentIntent->id]);
        $this->stripeClientSecret = $paymentIntent->client_secret;
        $this->submitted = true;
    } catch (\Exception $e) {
        $donation->update(['status' => DonationStatus::Failed]);
        session()->flash('error', 'Payment could not be processed. Please try again.');
    }
}
```

- [ ] **Step 3: Update donation form view with Stripe Elements card**

Replace the submit button section and thank-you section to include card form and Stripe Elements confirmation using Alpine.js:

```blade
@if ($submitted)
    <div class="py-10 text-center" x-data="stripePayment('{{ $stripeClientSecret }}')" x-init="init">
        <div x-show="loading" class="py-10 text-center">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-blue-50 text-2xl text-blue-600">
                <svg class="size-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>
            <h2 class="text-xl font-semibold">Processing payment...</h2>
            <p class="mt-2 text-sm text-slate-500">Please wait while we process your donation.</p>
        </div>

        <div x-show="success" class="py-10 text-center">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600">
                &check;
            </div>
            <h2 class="text-xl font-semibold">Thank you!</h2>
            <p class="mt-2 text-sm text-slate-500">{{ $this->config('success_message', 'Thank you for your donation!') }}</p>
        </div>

        <div x-show="error" class="py-10 text-center">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-red-50 text-2xl text-red-600">
                &times;
            </div>
            <h2 class="text-xl font-semibold">Payment failed</h2>
            <p class="mt-2 text-sm text-slate-500" x-text="errorMessage"></p>
            <button type="button" @click="window.location.reload()" class="mt-4 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                Try again
            </button>
        </div>
    </div>
@else
    <form wire:submit="submit" class="space-y-4">
        {{-- existing form fields --}}
        <div id="card-element" class="rounded-md border border-slate-200 p-3"></div>
        <div id="card-errors" class="text-sm text-red-600"></div>

        <button
            type="submit"
            class="min-h-16 w-full rounded-md bg-blue-600 px-4 text-lg font-semibold text-white shadow-sm transition hover:bg-blue-700 data-loading:pointer-events-none data-loading:opacity-70"
        >
            {{ $submitText }}
        </button>
    </form>
@endif

@script
<script>
    Alpine.data('stripePayment', (clientSecret) => ({
        loading: true,
        success: false,
        error: false,
        errorMessage: '',

        async init() {
            const stripe = Stripe(window.stripePublishableKey);
            const elements = stripe.elements();
            const card = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        fontFamily: 'Manrope, sans-serif',
                    },
                },
            });
            card.mount('#card-element');

            const { paymentIntent, error: confirmError } = await stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: card,
                },
            });

            if (confirmError) {
                this.loading = false;
                this.error = true;
                this.errorMessage = confirmError.message;
                return;
            }

            if (paymentIntent.status === 'succeeded') {
                this.loading = false;
                this.success = true;
            }
        },
    }));
</script>
@endscript
```

- [ ] **Step 4: Run tests to ensure form still works**

Run: `php artisan test --compact --filter=HostedDonationFormTest`

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/DonationForm.php resources/views/livewire/donation-form.blade.php resources/views/layouts/donation.blade.php
git commit -m "feat: integrate Stripe Elements into donation form"
```

---

### Task 4: Build Stripe Webhook Controller & Job

**Files:**
- Create: `app/Jobs/ProcessStripeWebhook.php`
- Create: `app/Http/Controllers/StripeWebhookController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create ProcessStripeWebhook job**

```php
<?php

namespace App\Jobs;

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Strive\Stripe;
use Stripe\Event as StripeEvent;

class ProcessStripeWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $stripeEventJson,
    ) {}

    public function handle(): void
    {
        $event = StripeEvent::constructFrom(json_decode($this->stripeEventJson, true));

        WebhookLog::query()->create([
            'stripe_event_id' => $event->id,
            'type' => $event->type,
            'payload' => $event->toArray(),
            'status' => 'processing',
        ]);

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event),
            'invoice.paid' => $this->handleInvoicePaid($event),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'account.updated' => $this->handleAccountUpdated($event),
            default => null,
        };

        WebhookLog::query()
            ->where('stripe_event_id', $event->id)
            ->update(['status' => 'completed']);
    }

    private function handlePaymentIntentSucceeded(StripeEvent $event): void
    {
        $paymentIntent = $event->data->object;
        $donationId = $paymentIntent->metadata->donation_id ?? null;

        if ($donationId === null) {
            return;
        }

        $donation = Donation::query()->find($donationId);
        if ($donation === null) {
            return;
        }

        $chargeId = $paymentIntent->charges->data[0]->id ?? null;
        $stripeFee = 0;
        $balanceTransaction = $paymentIntent->charges->data[0]->balance_transaction ?? null;

        if ($balanceTransaction) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $bt = \Stripe\BalanceTransaction::retrieve($balanceTransaction);
            $stripeFee = (float) ($bt->fee / 100);
        }

        $donation->update([
            'status' => DonationStatus::Succeeded,
            'stripe_charge_id' => $chargeId,
            'stripe_fee' => $stripeFee,
            'net_amount' => (float) $donation->gross_amount - $stripeFee - (float) $donation->platform_fee,
        ]);

        // Update campaign collected_amount
        $donation->campaign->increment('collected_amount', (float) $donation->gross_amount);

        // Send receipt
        SendDonationReceipt::dispatch($donation);
    }

    private function handlePaymentIntentFailed(StripeEvent $event): void
    {
        $paymentIntent = $event->data->object;
        $donationId = $paymentIntent->metadata->donation_id ?? null;

        if ($donationId === null) {
            return;
        }

        Donation::query()->whereKey($donationId)->update([
            'status' => DonationStatus::Failed,
        ]);
    }

    private function handleInvoicePaid(StripeEvent $event): void
    {
        $invoice = $event->data->object;
        $subscriptionId = $invoice->subscription;

        if ($subscriptionId === null) {
            return;
        }

        $subscription = Subscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if ($subscription === null) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'retry_count' => 0,
            'current_period_start' => now()->timestamp($invoice->period_start ?? $invoice->created),
            'current_period_end' => now()->timestamp($invoice->period_end),
        ]);

        // Record the recurring donation
        $donation = Donation::query()->create([
            'campaign_id' => $subscription->campaign_id,
            'donor_id' => $subscription->donor_id,
            'subscription_id' => $subscription->getKey(),
            'gross_amount' => (float) ($invoice->amount_paid / 100),
            'stripe_fee' => 0,
            'platform_fee' => 0,
            'net_amount' => (float) ($invoice->amount_paid / 100),
            'currency' => $invoice->currency,
            'status' => DonationStatus::Succeeded,
            'type' => \App\Enums\DonationType::Recurring,
            'stripe_payment_intent_id' => $invoice->payment_intent,
            'stripe_charge_id' => $invoice->charge,
        ]);

        $donation->campaign->increment('collected_amount', (float) $donation->gross_amount);

        SendDonationReceipt::dispatch($donation);
    }

    private function handleInvoicePaymentFailed(StripeEvent $event): void
    {
        $invoice = $event->data->object;
        $subscriptionId = $invoice->subscription;

        if ($subscriptionId === null) {
            return;
        }

        $subscription = Subscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if ($subscription === null) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::PastDue,
            'retry_count' => $subscription->retry_count + 1,
        ]);
    }

    private function handleSubscriptionDeleted(StripeEvent $event): void
    {
        $stripeSubscription = $event->data->object;

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscription->id)
            ->update(['status' => SubscriptionStatus::Cancelled, 'cancelled_at' => now()]);
    }

    private function handleSubscriptionUpdated(StripeEvent $event): void
    {
        $stripeSubscription = $event->data->object;

        $status = match ($stripeSubscription->status) {
            'active' => SubscriptionStatus::Active,
            'past_due' => SubscriptionStatus::PastDue,
            'canceled' => SubscriptionStatus::Cancelled,
            'incomplete' => SubscriptionStatus::Incomplete,
            'paused' => SubscriptionStatus::Paused,
            default => null,
        };

        if ($status === null) {
            return;
        }

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscription->id)
            ->update(['status' => $status]);
    }

    private function handleAccountUpdated(StripeEvent $event): void
    {
        $account = $event->data->object;

        Organization::query()
            ->where('stripe_account_id', $account->id)
            ->update([
                'stripe_onboarded' => $account->charges_enabled,
            ]);
    }
}
```

- [ ] **Step 2: Create StripeWebhookController**

```php
<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhook;
use Illuminate\Http\Request;
use Stripe\Stripe;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        ProcessStripeWebhook::dispatch($payload);

        return response()->json(['received' => true]);
    }
}
```

- [ ] **Step 3: Add webhook route to routes/web.php**

```php
use App\Http\Controllers\StripeWebhookController;

// Stripe webhook (no CSRF, no auth)
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
```

Also add to `app/Http/Middleware/VerifyCsrfToken.php` or the bootstrap/app.php to exclude webhook route from CSRF. Check current setup:

```php
// In bootstrap/app.php or exceptions:
->withExceptions(function (Exceptions $exceptions) {
    //
})
```

Actually for Laravel 11+, add to `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    // ...
)
```

Let me check the current bootstrap/app.php:

- [ ] **Check bootstrap/app.php for CSRF exclusion**

Read `bootstrap/app.php` → check if `except()` method exists, add:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        '/stripe/webhook',
    ]);
})
```

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/ProcessStripeWebhook.php app/Http/Controllers/StripeWebhookController.php routes/web.php
git commit -m "feat: add Stripe webhook controller and async processing job"
```

---

### Task 5: Stripe Connect Express Onboarding

**Files:**
- Create: `app/Actions/Stripe/CreateConnectAccount.php`
- Modify: `app/Filament/Resources/Organizations/Pages/EditOrganization.php`
- Modify: `app/Filament/App/Pages/Dashboard.php`
- Create: `resources/views/filament/app/pages/dashboard.blade.php`

- [ ] **Step 1: Create CreateConnectAccount action**

```php
<?php

namespace App\Actions\Stripe;

use App\Models\Organization;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;

class CreateConnectAccount
{
    public function create(Organization $organization): Account
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $account = Account::create([
            'type' => 'express',
            'country' => 'MY',
            'email' => $organization->contact_email,
            'business_type' => 'non_profit',
            'business_profile' => [
                'name' => $organization->name,
                'url' => $organization->website_url,
            ],
            'metadata' => [
                'organization_id' => (string) $organization->getKey(),
            ],
        ]);

        $organization->update(['stripe_account_id' => $account->id]);

        return $account;
    }

    public function generateOnboardingLink(Organization $organization): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $accountLink = AccountLink::create([
            'account' => $organization->stripe_account_id,
            'refresh_url' => route('filament.app.pages.dashboard'),
            'return_url' => route('filament.app.pages.dashboard'),
            'type' => 'account_onboarding',
        ]);

        return $accountLink->url;
    }

    public function generateDashboardLink(Organization $organization): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $accountLink = AccountLink::create([
            'account' => $organization->stripe_account_id,
            'refresh_url' => route('filament.app.pages.dashboard'),
            'return_url' => route('filament.app.pages.dashboard'),
            'type' => 'account_update',
        ]);

        return $accountLink->url;
    }
}
```

- [ ] **Step 2: Update EditOrganization to create Stripe account on approve**

Add to the approve action in `EditOrganization.php`:

```php
use App\Actions\Stripe\CreateConnectAccount;

// In the approve action:
Action::make('approve')
    ->action(function () {
        $this->record->update([
            'status' => OrganizationStatus::Active,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Create Stripe Connect Express account
        try {
            app(CreateConnectAccount::class)->create($this->record);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Organization approved, but Stripe account creation failed')
                ->body($e->getMessage())
                ->warning()
                ->send();
        }

        $this->refreshFormData(['status']);

        Notification::make()
            ->title('Organization approved')
            ->success()
            ->send();
    })
```

- [ ] **Step 3: Add "Sambung Stripe" button to NGO dashboard**

Create `resources/views/filament/app/pages/dashboard.blade.php`:

```blade
<x-filament::page>
    @php
        $org = auth()->user()->organization;
        $needsStripe = $org && $org->stripe_account_id && ! $org->stripe_onboarded;
        $noStripeAccount = $org && ! $org->stripe_account_id;
    @endphp

    @if ($needsStripe)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">Stripe Onboarding</h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">Sambung akaun Stripe untuk mula menerima derma.</p>
                </div>
                <a
                    href="{{ app(App\Actions\Stripe\CreateConnectAccount::class)->generateOnboardingLink($org) }}"
                    class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                >
                    Sambung Stripe
                </a>
            </div>
        </div>
    @endif

    {{ \Filament\Widgets\AccountWidget::make() }}
</x-filament::page>
```

- [ ] **Step 4: Update App\Dashboard page to use the custom view**

Update `App\Filament\App\Pages\Dashboard.php`:

```php
protected string $view = 'filament.app.pages.dashboard';
```

- [ ] **Step 5: Commit**

```bash
git add app/Actions/Stripe/CreateConnectAccount.php app/Filament/Resources/Organizations/Pages/EditOrganization.php app/Filament/App/Pages/Dashboard.php resources/views/filament/app/pages/dashboard.blade.php
git commit -m "feat: add Stripe Connect Express onboarding flow"
```

---

### Task 6: Email Notifications (Donation Receipt)

**Files:**
- Create: `app/Mail/DonationReceipt.php`
- Create: `resources/views/emails/donation-receipt.blade.php`
- Create: `app/Jobs/SendDonationReceipt.php`

- [ ] **Step 1: Create DonationReceipt mailable**

```php
<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Donation Receipt — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.donation-receipt',
        );
    }
}
```

- [ ] **Step 2: Create donation receipt email template**

```blade
<x-mail::message>
# Thank you for your donation!

Hi **{{ $donation->donor->name }}**,

Your donation of **RM {{ number_format($donation->gross_amount, 2) }}** to **{{ $donation->campaign->title }}** has been received successfully.

@if ($donation->type === \App\Enums\DonationType::Recurring)
This is a recurring donation. You will receive a receipt for each successful payment.
@endif

**Receipt Details:**
- **Amount:** RM {{ number_format($donation->gross_amount, 2) }}
- **Campaign:** {{ $donation->campaign->title }}
- **Organization:** {{ $donation->campaign->organization->name }}
- **Date:** {{ $donation->created_at->format('d M Y, h:i A') }}
- **Status:** Successful

@if ($donation->donor->magic_token)
[View your donation history]({{ route('donor.login', ['token' => $donation->donor->magic_token]) }})
@endif

Thank you for your support!

<x-mail::subcopy>
If you have any questions, please contact the organization directly.
</x-mail::subcopy>
</x-mail::message>
```

Note: Requires `laravel-mail-template` or similar. Check if laravel/mail provides the `x-mail::message` component. If not, use plain blade.

Actually, Laravel's markdown mail provides those components. They're built-in. Let me simplify - just use regular blade for now:

```blade
{{-- resources/views/emails/donation-receipt.blade.php --}}
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Manrope, sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Thank you for your donation!</h1>

        <p>Hi <strong>{{ $donation->donor->name }}</strong>,</p>

        <p>Your donation of <strong>RM {{ number_format($donation->gross_amount, 2) }}</strong> to <strong>{{ $donation->campaign->title }}</strong> has been received successfully.</p>

        @if ($donation->type === \App\Enums\DonationType::Recurring)
            <p><em>This is a recurring donation. You will receive a receipt for each successful payment.</em></p>
        @endif

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Amount</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: 600;">RM {{ number_format($donation->gross_amount, 2) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Campaign</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->title }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Organization</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->campaign->organization->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;">Date</td><td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{ $donation->created_at->format('d M Y, h:i A') }}</td></tr>
            <tr><td style="padding: 8px; color: #64748b;">Status</td><td style="padding: 8px; color: #16a34a; font-weight: 600;">Successful</td></tr>
        </table>

        <p>Thank you for your support!</p>
    </div>
</body>
</html>
```

- [ ] **Step 3: Create SendDonationReceipt job**

```php
<?php

namespace App\Jobs;

use App\Mail\DonationReceipt;
use App\Models\Donation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDonationReceipt implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation,
    ) {}

    public function handle(): void
    {
        Mail::to($this->donation->donor->email)
            ->send(new DonationReceipt($this->donation));
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Mail/DonationReceipt.php resources/views/emails/donation-receipt.blade.php app/Jobs/SendDonationReceipt.php
git commit -m "feat: add donation receipt email notification"
```

---

### Task 7: Donor Portal — Magic Link & Pages

**Files:**
- Create: `app/Http/Controllers/DonorAuthController.php`
- Create: `app/Http/Controllers/DonorPortalController.php`
- Create: `resources/views/donor/layout.blade.php`
- Create: `resources/views/donor/donations.blade.php`
- Create: `resources/views/donor/subscriptions.blade.php`
- Create: `app/Mail/MagicLink.php`
- Create: `resources/views/emails/magic-link.blade.php`
- Modify: `app/Models/Donor.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Add magic link methods to Donor model**

```php
// app/Models/Donor.php
use Illuminate\Support\Str;

// Add methods:
public function generateMagicToken(): string
{
    $token = Str::random(64);
    $this->update([
        'magic_token' => $token,
        'magic_token_expires_at' => now()->addHours(24),
    ]);

    return $token;
}

public function isValidMagicToken(string $token): bool
{
    return $this->magic_token === $token
        && $this->magic_token_expires_at !== null
        && $this->magic_token_expires_at->isFuture();
}
```

- [ ] **Step 2: Create MagicLink mailable**

```php
<?php

namespace App\Mail;

use App\Models\Donor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donor $donor,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Donation Portal Login Link',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.magic-link',
        );
    }
}
```

- [ ] **Step 3: Create magic link email template**

```blade
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: Manrope, sans-serif; line-height: 1.6; color: #1a1a2e;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #0f766e;">Your Donation Portal</h1>

        <p>Hi <strong>{{ $donor->name }}</strong>,</p>

        <p>Click the button below to access your donation portal where you can view your donation history and manage subscriptions.</p>

        <a href="{{ route('donor.login', ['token' => $token]) }}"
           style="display: inline-block; padding: 12px 24px; background-color: #0f766e; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0;">
            Access Donation Portal
        </a>

        <p style="color: #94a3b8; font-size: 14px;">This link expires in 24 hours. If you did not request this, please ignore this email.</p>
    </div>
</body>
</html>
```

- [ ] **Step 4: Create DonorAuthController**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Donor;

class DonorAuthController extends Controller
{
    public function login(string $token)
    {
        $donor = Donor::query()
            ->where('magic_token', $token)
            ->where('magic_token_expires_at', '>', now())
            ->first();

        if ($donor === null) {
            return redirect()->route('home')->with('error', 'Invalid or expired login link.');
        }

        session()->put('donor_id', $donor->getKey());

        return redirect()->route('donor.donations');
    }

    public function logout()
    {
        session()->forget('donor_id');

        return redirect()->route('home');
    }
}
```

- [ ] **Step 5: Create DonorPortalController**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Donor;

class DonorPortalController extends Controller
{
    private function getDonor(): ?Donor
    {
        $donorId = session('donor_id');
        if ($donorId === null) {
            return null;
        }

        return Donor::query()->with('donations.campaign.organization', 'subscriptions.campaign')->find($donorId);
    }

    public function donations()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('home');
        }

        return view('donor.donations', [
            'donor' => $donor,
            'donations' => $donor->donations()->latest()->paginate(10),
        ]);
    }

    public function subscriptions()
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('home');
        }

        return view('donor.subscriptions', [
            'donor' => $donor,
            'subscriptions' => $donor->subscriptions()->latest()->paginate(10),
        ]);
    }

    public function cancelSubscription(string $id)
    {
        $donor = $this->getDonor();
        if ($donor === null) {
            return redirect()->route('home');
        }

        $subscription = $donor->subscriptions()->findOrFail($id);
        $subscription->update([
            'status' => \App\Enums\SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return redirect()->route('donor.subscriptions')->with('success', 'Subscription cancelled.');
    }
}
```

- [ ] **Step 6: Create donor layout view**

```blade
{{-- resources/views/donor/layout.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Donor Portal') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 antialiased dark:bg-neutral-950">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-lg font-semibold text-teal-700">{{ config('app.name') }}</a>
            <a href="{{ route('donor.logout') }}" class="text-sm text-slate-500 hover:text-slate-700">Logout</a>
        </div>

        <nav class="mb-6 flex gap-4 border-b border-slate-200 pb-4">
            <a href="{{ route('donor.donations') }}" class="text-sm font-medium {{ request()->routeIs('donor.donations') ? 'text-teal-700' : 'text-slate-500' }}">
                Donation History
            </a>
            <a href="{{ route('donor.subscriptions') }}" class="text-sm font-medium {{ request()->routeIs('donor.subscriptions') ? 'text-teal-700' : 'text-slate-500' }}">
                Subscriptions
            </a>
        </nav>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @yield('content')
    </div>
</body>
</html>
```

- [ ] **Step 7: Create donations view**

```blade
{{-- resources/views/donor/donations.blade.php --}}
@extends('donor.layout')

@section('title', 'Donation History')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Donation History</h1>
    <p class="mt-1 text-sm text-slate-500">Hi {{ $donor->name }}, here are your donations.</p>

    <div class="mt-6 space-y-3">
        @forelse ($donations as $donation)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">{{ $donation->campaign->title }}</p>
                        <p class="text-sm text-slate-500">{{ $donation->campaign->organization->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-semibold text-slate-900">RM {{ number_format($donation->gross_amount, 2) }}</p>
                        <p class="text-xs text-slate-400">{{ $donation->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $donation->status === \App\Enums\DonationStatus::Succeeded ? 'bg-emerald-100 text-emerald-700' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Pending ? 'bg-amber-100 text-amber-700' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Failed ? 'bg-red-100 text-red-700' : '' }}
                        {{ $donation->status === \App\Enums\DonationStatus::Refunded ? 'bg-slate-100 text-slate-600' : '' }}">
                        {{ $donation->status->value }}
                    </span>
                    <span class="ml-2 inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">
                        {{ $donation->type === \App\Enums\DonationType::Recurring ? 'Recurring' : 'One-time' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500">
                No donations yet.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $donations->links() }}
    </div>
@endsection
```

- [ ] **Step 8: Create subscriptions view**

```blade
{{-- resources/views/donor/subscriptions.blade.php --}}
@extends('donor.layout')

@section('title', 'My Subscriptions')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">My Subscriptions</h1>
    <p class="mt-1 text-sm text-slate-500">Manage your recurring donations.</p>

    <div class="mt-6 space-y-3">
        @forelse ($subscriptions as $subscription)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">{{ $subscription->campaign->title }}</p>
                        <p class="text-sm text-slate-500">{{ $subscription->campaign->organization->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-semibold text-slate-900">RM {{ number_format($subscription->amount, 2) }}/{{ $subscription->interval->value }}</p>
                        <p class="text-xs text-slate-400">
                            @if ($subscription->current_period_end)
                                Next: {{ $subscription->current_period_end->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Active ? 'bg-emerald-100 text-emerald-700' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Cancelled ? 'bg-slate-100 text-slate-600' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::PastDue ? 'bg-red-100 text-red-700' : '' }}
                        {{ $subscription->status === \App\Enums\SubscriptionStatus::Paused ? 'bg-amber-100 text-amber-700' : '' }}">
                        {{ $subscription->status->value }}
                    </span>

                    @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                        <form action="{{ route('donor.subscriptions.cancel', $subscription) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this subscription?')">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">Cancel</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500">
                No subscriptions yet.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $subscriptions->links() }}
    </div>
@endsection
```

- [ ] **Step 9: Add donor routes to routes/web.php**

```php
use App\Http\Controllers\DonorAuthController;
use App\Http\Controllers\DonorPortalController;

// Donor portal
Route::prefix('donor')->name('donor.')->group(function () {
    Route::get('login/{token}', [DonorAuthController::class, 'login'])->name('login');
    Route::get('logout', [DonorAuthController::class, 'logout'])->name('logout');
    Route::get('donations', [DonorPortalController::class, 'donations'])->name('donations');
    Route::get('subscriptions', [DonorPortalController::class, 'subscriptions'])->name('subscriptions');
    Route::post('subscriptions/{subscription}/cancel', [DonorPortalController::class, 'cancelSubscription'])->name('subscriptions.cancel');
});
```

- [ ] **Step 10: Generate magic token in Donor model on creation**

Add to Donor model boot:

```php
protected static function booted(): void
{
    static::creating(function (Donor $donor) {
        if (! $donor->magic_token) {
            $donor->magic_token = Str::random(64);
            $donor->magic_token_expires_at = now()->addYears(5);
        }
    });
}
```

- [ ] **Step 11: Commit**

```bash
git add app/Http/Controllers/DonorAuthController.php app/Http/Controllers/DonorPortalController.php resources/views/donor/ app/Mail/MagicLink.php resources/views/emails/magic-link.blade.php app/Models/Donor.php routes/web.php
git commit -m "feat: add donor portal with magic link auth"
```

---

### Task 8: Update bootstrap/app.php for CSRF Exclusion

**Files:**
- Modify: `bootstrap/app.php`

- [ ] **Step 1: Read current bootstrap/app.php**

```bash
cat bootstrap/app.php
```

- [ ] **Step 2: Add CSRF exclusion for webhook route**

If using Laravel 11 style:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        '/stripe/webhook',
    ]);
})
```

- [ ] **Step 3: Commit**

```bash
git add bootstrap/app.php
git commit -m "fix: exclude Stripe webhook from CSRF protection"
```

---

### Task 9: Update Tests

**Files:**
- Create: `tests/Feature/StripePaymentIntentTest.php`
- Create: `tests/Feature/StripeWebhookTest.php`
- Create: `tests/Feature/DonorPortalTest.php`
- Create: `tests/Feature/Mail/DonationReceiptTest.php`
- Modify: `tests/Feature/HostedDonationFormTest.php`

- [ ] **Step 1: Update HostedDonationFormTest to reflect new flow**

```php
// Update the test that creates a donation
it('validates and creates donation record before payment', function () {
    // same setup...
    Livewire::test(DonationForm::class, ['element' => $element])
        ->set('amount', 100)
        ->set('name', 'Wan Mohd Afendi')
        ->set('email', 'wan@example.test')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $donor = Donor::query()->where('email', 'wan@example.test')->firstOrFail();
    $donation = Donation::query()->whereBelongsTo($donor)->firstOrFail();

    expect($donation->status)->toBe(DonationStatus::Pending);
});
```

- [ ] **Step 2: Create StripePaymentIntentTest**

```php
<?php

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

it('returns validation errors for invalid input', function () {
    $response = $this->postJson(route('stripe.payment-intent'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['campaign_id', 'donor_name', 'donor_email', 'amount']);
});

it('creates a pending donation and returns client_secret on success', function () {
    // Mock Stripe
    Stripe\Stripe::setApiKey('sk_test_xxxxxxxx');
    // This test requires actual Stripe interaction or a mock
    // For now, test that the endpoint validates and creates a donation record
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    $response = $this->postJson(route('stripe.payment-intent'), [
        'campaign_id' => $campaign->getKey(),
        'donor_name' => 'Test Donor',
        'donor_email' => 'test@example.com',
        'amount' => 50,
        'currency' => 'myr',
        'type' => 'one_time',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['client_secret', 'donation_id']);

    $donation = Donation::query()->find($response->json('donation_id'));
    expect($donation)->not->toBeNull()
        ->and($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->gross_amount)->toBe('50.00');
});
```

- [ ] **Step 3: Create StripeWebhookTest**

```php
<?php

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Campaign;
use App\Models\Organization;

it('rejects webhook with invalid signature', function () {
    $response = $this->postJson(route('stripe.webhook'), [
        'type' => 'payment_intent.succeeded',
    ], [
        'Stripe-Signature' => 'invalid',
    ]);

    $response->assertStatus(400);
});
```

- [ ] **Step 4: Create DonorPortalTest**

```php
<?php

use App\Models\Donor;

it('logs in with valid magic token', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'valid-token-123',
        'magic_token_expires_at' => now()->addHours(24),
    ]);

    $this->get(route('donor.login', ['token' => 'valid-token-123']))
        ->assertRedirect(route('donor.donations'));

    $this->assertEquals(session('donor_id'), $donor->getKey());
});

it('rejects expired magic token', function () {
    Donor::factory()->create([
        'magic_token' => 'expired-token',
        'magic_token_expires_at' => now()->subHour(),
    ]);

    $this->get(route('donor.login', ['token' => 'expired-token']))
        ->assertRedirect(route('home'));
});

it('requires auth for donor portal pages', function () {
    $this->get(route('donor.donations'))->assertRedirect(route('home'));
    $this->get(route('donor.subscriptions'))->assertRedirect(route('home'));
});
```

- [ ] **Step 5: Run all tests**

Run: `php artisan test --compact`
Expected: All passing (note: StripePaymentIntentTest may need actual Stripe keys to fully pass).

- [ ] **Step 6: Run Pint**

Run: `vendor/bin/pint --format agent`

- [ ] **Step 7: Commit**

```bash
git add tests/
git commit -m "test: add tests for Stripe payment, webhook, and donor portal"
```

---

### Task 10: Final Verification

- [ ] **Step 1: Run full test suite**

```bash
php artisan test --compact
```

- [ ] **Step 2: Run Pint**

```bash
vendor/bin/pint --format agent
```

- [ ] **Step 3: Verify routes**

```bash
php artisan route:list --except-vendor | grep -E 'stripe|donor'
```
