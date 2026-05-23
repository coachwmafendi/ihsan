# Multi-Step Checkout Flow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor `donation-form.blade.php` from a single scrollable form into a 3-step checkout flow (Amount → Details → Payment) using Alpine.js step state, matching the Fundraise Up UX pattern.

**Architecture:** Step navigation is purely client-side via Alpine.js `currentStep` state — zero Livewire roundtrips for navigation. `DonationForm.php` is unchanged except the `render()` method. The blade template is rewritten to show/hide step sections with `x-show`. Stripe submit still fires from Step 3 only.

**Tech Stack:** Livewire 4 SFC, Alpine.js (bundled with Livewire), Stripe.js v3, Tailwind CSS v4, Pest v4

---

## File Map

| File | Change |
|------|--------|
| `resources/views/livewire/donation-form.blade.php` | Major rewrite — step UI |
| `tests/Feature/HostedDonationFormTest.php` | Update HTML assertions for new structure |
| `app/Livewire/DonationForm.php` | No change |

---

## Task 1: Add step state and navigation to Alpine `donationForm`

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php` (Alpine `@script` block only)
- Test: `tests/Feature/HostedDonationFormTest.php`

- [ ] **Step 1: Write failing test — verify step state variables exist in rendered HTML**

```php
it('renders step state variables in Alpine donationForm', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 50],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('currentStep', false)
        ->assertSee('stepErrors', false)
        ->assertSee('nextStep()', false)
        ->assertSee('prevStep()', false);
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
php artisan test --compact --filter="renders step state variables"
```

Expected: FAIL — `currentStep` not found in HTML.

- [ ] **Step 3: Update Alpine `donationForm` data function in the `@script` block**

Replace the Alpine data object (everything from `return {` to the closing `};` of the outer return) with:

```js
return {
    frequency: initialFrequency,
    amount: initialAmount,
    donorName: initialName,
    donorEmail: initialEmail,
    donorPhone: initialPhone,
    processing: false,
    currentStep: 1,
    stepErrors: {},
    cardError: '',

    validateStep1() {
        this.stepErrors = {};
        const amt = parseFloat(this.amount);
        if (!amt || amt < 1) {
            this.stepErrors.amount = 'Please enter a valid amount (minimum RM 1).';
            return false;
        }
        if (amt > 100000) {
            this.stepErrors.amount = 'Amount cannot exceed RM 100,000.';
            return false;
        }
        return true;
    },

    validateStep2() {
        this.stepErrors = {};
        let valid = true;
        if (!this.donorName.trim()) {
            this.stepErrors.name = 'Name is required.';
            valid = false;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(this.donorEmail)) {
            this.stepErrors.email = 'Please enter a valid email address.';
            valid = false;
        }
        return valid;
    },

    nextStep() {
        if (this.currentStep === 1 && !this.validateStep1()) return;
        if (this.currentStep === 2 && !this.validateStep2()) return;
        this.currentStep++;
    },

    prevStep() {
        if (this.currentStep > 1) this.currentStep--;
    },

    async init() {
        stripe = connectedStripeAccountId
            ? Stripe(window.stripePublishableKey, { stripeAccount: connectedStripeAccountId })
            : Stripe(window.stripePublishableKey);
        const elements = stripe.elements({ locale: 'ms' });
        cardElement = elements.create('card', {
            hidePostalCode: true,
            style: {
                base: { fontSize: '16px', color: '#212830', '::placeholder': { color: '#94a3b8' } },
            },
        });
        cardElement.mount('#card-element');
        cardElement.on('change', (event) => {
            this.cardError = event.error ? event.error.message : '';
        });
    },

    async handleSubmit() {
        this.cardError = '';

        const { paymentMethod, error: paymentMethodError } = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: this.donorName,
                email: this.donorEmail,
                phone: this.donorPhone || undefined,
            },
        });

        if (paymentMethodError) {
            this.currentStep = 'error';
            this.cardError = paymentMethodError.message;
            return;
        }

        this.processing = true;
        $wire.$set('frequency', this.frequency, false);
        $wire.$set('amount', this.amount, false);
        $wire.$set('name', this.donorName, false);
        $wire.$set('email', this.donorEmail, false);
        $wire.$set('phone', this.donorPhone, false);

        let clientSecret;
        try {
            clientSecret = await $wire.submit();
        } catch (e) {
            this.processing = false;
            return;
        }

        if (!clientSecret) {
            this.processing = false;
            return;
        }

        const { paymentIntent, error: confirmError } = await stripe.confirmCardPayment(clientSecret, {
            receipt_email: this.donorEmail,
            payment_method: paymentMethod.id,
        });

        if (confirmError) {
            this.processing = false;
            this.currentStep = 'error';
            this.cardError = confirmError.message;
            return;
        }

        if (paymentIntent.status === 'succeeded') {
            await $wire.confirmPayment(paymentIntent.id);
            this.processing = false;
            this.currentStep = 'success';
        }
    },
};
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
php artisan test --compact --filter="renders step state variables"
```

Expected: PASS.

- [ ] **Step 5: Run full test suite to confirm no regressions**

```bash
php artisan test --compact tests/Feature/HostedDonationFormTest.php
```

Note expected failures — the HTML assertion tests will fail because the template still uses old `success`/`error` variable names. Record which tests fail; they will be fixed in Task 5.

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/donation-form.blade.php
git commit -m "feat: add Alpine step state and navigation to donation form"
```

---

## Task 2: Rebuild Step 1 — Amount & Frequency

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php` (form content inside `x-data` div)

- [ ] **Step 1: Replace the `<form>` block inside `x-data="donationForm(...)"` with step-aware structure**

Find this opening (around line 190–191):
```html
<div x-data="donationForm(@js($frequency), @js($amount), @js($name), @js($email), @js($phone), @js($connectedStripeAccountId))">
    <div x-show="!success && !error">
        <form class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}" @submit.prevent="handleSubmit">
```

Replace with:
```html
<div x-data="donationForm(@js($frequency), @js($amount), @js($name), @js($email), @js($phone), @js($connectedStripeAccountId))">

    {{-- Step progress indicator --}}
    <div x-show="typeof currentStep === 'number'" class="mb-4 text-sm text-slate-500">
        <span x-show="currentStep === 1">Step <strong class="text-slate-800">1</strong> of 3 — Choose Amount</span>
        <span x-show="currentStep === 2" x-cloak>Step <strong class="text-slate-800">2</strong> of 3 — Your Details</span>
        <span x-show="currentStep === 3" x-cloak>Step <strong class="text-slate-800">3</strong> of 3 — Payment</span>
    </div>

    {{-- Step 1: Amount & Frequency --}}
    <div x-show="currentStep === 1" class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}">
```

- [ ] **Step 2: Add Step 1 content (frequency toggle + amounts + continue button)**

After the opening `<div x-show="currentStep === 1"...>`, paste:

```html
        <div class="grid grid-cols-2 gap-2">
            <button
                type="button"
                x-on:click="frequency = 'one_time'"
                x-bind:class="frequency === 'one_time' ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                class="min-h-10 rounded-lg border bg-white px-3 text-sm font-semibold transition"
            >
                Give once
            </button>

            @if ($allowMonthly)
                <button
                    type="button"
                    x-on:click="frequency = 'monthly'"
                    x-bind:class="frequency === 'monthly' ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                    class="min-h-10 rounded-lg border bg-white px-3 text-sm font-semibold transition"
                >
                    <span style="color: {{ $iconColor }};">&hearts;</span>
                    Monthly
                </button>
            @endif
        </div>

        @if ($this->config('show_suggested', true))
            <div x-show="frequency === 'one_time'" class="grid grid-cols-3 gap-2">
                @foreach ($oneTimeAmounts as $amount)
                    <button
                        type="button"
                        wire:key="one_time_{{ $amount }}"
                        x-on:click="amount = {{ $amount }}"
                        x-bind:class="Number(amount) === {{ $amount }} ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
                        class="min-h-12 rounded-lg border bg-white px-2 text-sm font-semibold transition"
                    >
                        RM {{ number_format($amount) }}
                    </button>
                @endforeach
            </div>

            <div x-show="frequency === 'monthly'" class="grid grid-cols-3 gap-2">
                @foreach ($monthlyAmounts as $amount)
                    <button
                        type="button"
                        wire:key="monthly_{{ $amount }}"
                        x-on:click="amount = {{ $amount }}"
                        x-bind:class="Number(amount) === {{ $amount }} ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
                        class="min-h-12 rounded-lg border bg-white px-2 text-sm font-semibold transition"
                    >
                        RM {{ number_format($amount) }}
                    </button>
                @endforeach
            </div>
        @endif

        @if ($this->config('show_amount_input', true))
            <label class="block">
                <span class="sr-only">Donation amount</span>
                <div class="flex min-h-14 items-center rounded-xl border border-slate-300 bg-white px-4 transition focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-600/20">
                    <span class="{{ $usesSecureDonationShell ? 'text-2xl' : 'text-base' }} font-semibold text-slate-700">RM</span>
                    <input
                        x-model="amount"
                        type="number"
                        min="1"
                        step="1"
                        class="min-w-0 flex-1 border-0 bg-transparent px-2 text-3xl/none font-bold text-slate-950 outline-none placeholder:text-slate-300 sm:px-3 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                    />
                    <span class="text-sm font-medium text-slate-500">MYR</span>
                </div>
                <div x-show="stepErrors.amount" x-cloak class="mt-1 text-sm text-red-600" x-text="stepErrors.amount"></div>
            </label>
        @endif

        <button
            type="button"
            @click="nextStep()"
            class="min-h-12 w-full rounded-lg bg-teal-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.98]"
        >
            Continue &rarr;
        </button>
    </div>{{-- end Step 1 --}}
```

- [ ] **Step 3: Run render test to confirm Step 1 elements visible**

```bash
php artisan test --compact --filter="renders a hosted donation form for an active form element token"
```

Expected: PASS (Give once, Monthly, amount buttons still in HTML).

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/donation-form.blade.php
git commit -m "feat: add Step 1 amount and frequency UI to multi-step checkout"
```

---

## Task 3: Rebuild Step 2 — Donor Details

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php`

- [ ] **Step 1: Add Step 2 block after the closing `</div>{{-- end Step 1 --}}`**

```html
{{-- Step 2: Donor Details --}}
<div x-show="currentStep === 2" x-cloak class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}">

    <button
        type="button"
        @click="prevStep()"
        class="mb-1 flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition"
    >
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back
    </button>

    <div class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Your details</p>

        <label class="block">
            <span class="mb-1 block text-sm font-medium text-slate-700">Name</span>
            <input
                wire:model="name"
                x-model="donorName"
                type="text"
                autocomplete="name"
                class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                placeholder="Your full name"
            />
            <div x-show="stepErrors.name" x-cloak class="mt-1 text-sm text-red-600" x-text="stepErrors.name"></div>
            @error('name')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="block">
            <span class="mb-1 block text-sm font-medium text-slate-700">Email</span>
            <input
                wire:model="email"
                x-model="donorEmail"
                type="email"
                autocomplete="email"
                class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                placeholder="you@example.com"
            />
            <div x-show="stepErrors.email" x-cloak class="mt-1 text-sm text-red-600" x-text="stepErrors.email"></div>
            @error('email')<span class="mt-1 block text-sm text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="block">
            <span class="mb-1 block text-sm font-medium text-slate-700">Phone <span class="font-normal text-slate-400">(optional)</span></span>
            <input
                wire:model="phone"
                x-model="donorPhone"
                type="tel"
                autocomplete="tel"
                class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                placeholder="012-345 6789"
            />
        </label>
    </div>

    @if ($showDedication)
        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
            <input wire:model="dedicate" type="checkbox" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600" />
            Dedicate this donation
        </label>
    @endif

    @if ($showComment)
        <label class="block">
            <span class="mb-0.5 block text-sm font-medium text-slate-700">Comment <span class="font-normal text-slate-400">(optional)</span></span>
            <textarea wire:model="comment" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/10" placeholder="Leave a message..."></textarea>
        </label>
    @endif

    <button
        type="button"
        @click="nextStep()"
        class="min-h-12 w-full rounded-lg bg-teal-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.98]"
    >
        Continue &rarr;
    </button>
</div>{{-- end Step 2 --}}
```

- [ ] **Step 2: Write test verifying Step 2 fields exist in HTML**

Add to `HostedDonationFormTest.php`:

```php
it('renders donor details fields for step 2', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => [
            'show_dedication' => true,
            'show_comment' => true,
        ],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('x-show="currentStep === 2"', false)
        ->assertSee('x-model="donorName"', false)
        ->assertSee('x-model="donorEmail"', false)
        ->assertSee('x-model="donorPhone"', false)
        ->assertSee('Dedicate this donation')
        ->assertSee('Leave a message...');
});
```

- [ ] **Step 3: Run test**

```bash
php artisan test --compact --filter="renders donor details fields for step 2"
```

Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/donation-form.blade.php tests/Feature/HostedDonationFormTest.php
git commit -m "feat: add Step 2 donor details UI to multi-step checkout"
```

---

## Task 4: Rebuild Step 3 — Payment with Summary Bar

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php`

- [ ] **Step 1: Add Step 3 block after the closing `</div>{{-- end Step 2 --}}`**

```html
{{-- Step 3: Payment --}}
<div x-show="currentStep === 3" x-cloak class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}">

    <button
        type="button"
        @click="prevStep()"
        class="mb-1 flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition"
    >
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back
    </button>

    {{-- Summary bar --}}
    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm">
        <span class="font-semibold text-slate-800">
            RM <span x-text="Number(amount).toLocaleString()"></span>
        </span>
        <span class="text-slate-500">
            <span x-show="frequency === 'monthly'">Monthly</span>
            <span x-show="frequency !== 'monthly'">One-time</span>
        </span>
    </div>

    <div wire:ignore>
        <label class="mb-0.5 block text-sm font-medium text-slate-700">Card details</label>
        <div id="card-element" class="min-h-10 rounded-lg border border-slate-200 px-3 py-2.5 transition focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-600/10"></div>
        <div x-show="cardError" x-cloak class="mt-1 text-sm text-red-600" x-text="cardError"></div>
    </div>

    <form @submit.prevent="handleSubmit">
        <button
            type="submit"
            class="min-h-12 w-full rounded-lg bg-teal-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.98] disabled:opacity-60"
            x-bind:disabled="processing"
        >
            @if ($usesSecureDonationShell && in_array($submitText, ['Donate and Support', 'Donate Now'], true))
                <span x-show="!processing && frequency === 'monthly'">Donate monthly</span>
                <span x-show="!processing && frequency !== 'monthly'">Donate once</span>
            @else
                <span x-show="!processing">{{ $submitText }}</span>
            @endif
            <span x-show="processing" x-cloak>Processing...</span>
        </button>
    </form>
</div>{{-- end Step 3 --}}
```

- [ ] **Step 2: Write test verifying Step 3 payment elements exist**

Add to `HostedDonationFormTest.php`:

```php
it('renders payment step with summary bar and card element', function () {
    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_test_123',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 50, 'default_frequency' => 'monthly'],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('x-show="currentStep === 3"', false)
        ->assertSee('id="card-element"', false)
        ->assertSee('Donate monthly')
        ->assertSee('handleSubmit', false)
        ->assertSee('stripe.createPaymentMethod', false);
});
```

- [ ] **Step 3: Run test**

```bash
php artisan test --compact --filter="renders payment step with summary bar"
```

Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/donation-form.blade.php tests/Feature/HostedDonationFormTest.php
git commit -m "feat: add Step 3 payment UI with summary bar"
```

---

## Task 5: Update Success, Error, and Processing States

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php`

- [ ] **Step 1: Add processing, success, and error blocks after `</div>{{-- end Step 3 --}}`**

```html
{{-- Processing --}}
<div x-show="processing" x-cloak class="py-8 text-center">
    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-teal-50">
        <svg class="size-5 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
    </div>
    <h2 class="text-base font-semibold text-slate-900">Processing payment...</h2>
    <p class="mt-1 text-sm text-slate-500">Please wait while we process your donation.</p>
</div>

{{-- Success --}}
<div x-show="currentStep === 'success'" x-cloak class="py-8 text-center">
    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-emerald-50">
        <svg class="size-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
    </div>
    <h2 class="text-base font-semibold text-slate-900">Thank you, <span x-text="donorName"></span>!</h2>
    <p class="mt-1 text-sm text-slate-500">Receipt sent to <span x-text="donorEmail"></span>.</p>
    <p class="mt-1 text-sm text-slate-500">{{ $this->config('success_message', 'Thank you for your donation!') }}</p>
    @if ($isPopup)
        <button
            type="button"
            @click="$dispatch('popup-close')"
            class="mt-4 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            Close
        </button>
    @endif
</div>

{{-- Error --}}
<div x-show="currentStep === 'error'" x-cloak class="py-8 text-center">
    <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-red-50">
        <svg class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </div>
    <h2 class="text-base font-semibold text-slate-900">Payment failed</h2>
    <p class="mt-1 text-sm text-slate-500" x-text="cardError"></p>
    <button
        type="button"
        @click="currentStep = 3"
        class="mt-4 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700"
    >
        Try again
    </button>
</div>
```

- [ ] **Step 2: Update existing test assertions that reference old `success`/`error` x-show patterns**

In `HostedDonationFormTest.php`, find all tests asserting old patterns and update:

```php
// OLD — remove these assertions:
->assertSee('x-show="!success && !error"', false)
->assertSee('x-show="processing && !success && !error" x-cloak', false)
->assertSee('x-show="success" x-cloak', false)
->assertSee('x-show="error" x-cloak', false)
->assertDontSee('x-show="!processing && !success && !error"', false)

// NEW — replace with:
->assertSee('x-show="currentStep === \'success\'"', false)
->assertSee('x-show="currentStep === \'error\'"', false)
->assertSee('x-show="processing"', false)
```

The test affected is `'renders a hosted donation form for an active form element token'`.

Update it:
```php
it('renders a hosted donation form for an active form element token', function () {
    $organization = Organization::factory()->create([
        'name' => 'Maahad Tahfiz Mumtazatut Taqwa',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'MTMT Development Fund',
        'suggested_amounts' => [30, 50, 100],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'form-token-123',
        'type' => ElementType::Form,
        'config' => [
            'title' => 'Your most generous donation',
            'submit_text' => 'Donate and Support',
            'default_amount' => 5,
            'suggested_amounts' => [200, 100, 50, 30, 10, 5],
            'allow_monthly' => true,
            'show_dedication' => true,
            'show_comment' => true,
        ],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('Maahad Tahfiz Mumtazatut Taqwa')
        ->assertSee('MTMT Development Fund')
        ->assertSee('Secure donation')
        ->assertSee('Give once')
        ->assertSee('Monthly')
        ->assertSee('RM 200')
        ->assertSee('RM 5')
        ->assertSee('Donate monthly')
        ->assertSee('x-show="currentStep === \'success\'"', false)
        ->assertSee('x-show="currentStep === \'error\'"', false)
        ->assertSee("x-on:click=\"frequency = 'one_time'\"", false)
        ->assertSee("x-on:click=\"frequency = 'monthly'\"", false)
        ->assertSee('$wire.$set(&#039;frequency&#039;, this.frequency, false)', false)
        ->assertSee('$wire.$set(&#039;amount&#039;, this.amount, false)', false)
        ->assertSee('x-show="processing"', false);
});
```

- [ ] **Step 3: Run affected tests**

```bash
php artisan test --compact tests/Feature/HostedDonationFormTest.php
```

Expected: All PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/donation-form.blade.php tests/Feature/HostedDonationFormTest.php
git commit -m "feat: update success/error states for multi-step checkout"
```

---

## Task 6: Mobile layout — hide left panel

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php` (popup left `<section>`)

- [ ] **Step 1: Find the popup left panel opening tag (around line 36)**

```html
<section class="lg:flex lg:min-h-0 lg:flex-col lg:border-r lg:border-slate-200">
```

Replace with:

```html
<section class="hidden lg:flex lg:min-h-0 lg:flex-col lg:border-r lg:border-slate-200">
```

- [ ] **Step 2: Write test verifying left panel hidden on mobile**

Add to `HostedDonationFormTest.php`:

```php
it('hides the left panel on mobile in popup mode', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'image_path' => 'campaigns/test.jpg',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Popup,
        'config' => ['template' => 'secure-donation'],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('hidden lg:flex lg:min-h-0 lg:flex-col lg:border-r lg:border-slate-200', false);
});
```

- [ ] **Step 3: Run test**

```bash
php artisan test --compact --filter="hides the left panel on mobile"
```

Expected: PASS.

- [ ] **Step 4: Run full test suite**

```bash
php artisan test --compact tests/Feature/HostedDonationFormTest.php
```

Expected: All PASS.

- [ ] **Step 5: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Final commit**

```bash
git add resources/views/livewire/donation-form.blade.php tests/Feature/HostedDonationFormTest.php
git commit -m "feat: hide left panel on mobile for popup checkout"
```

---

## Task 7: Remove old single-form block and clean up

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php`

- [ ] **Step 1: Delete the old single-form block**

After the step UI is in place, the original `<form>` block that contained all steps in one will still be present in the file. Identify it by its opening tag:

```html
{{-- DELETE from here: --}}
<form class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}" @submit.prevent="handleSubmit">
    ...
</form>
{{-- to the closing </form> tag of the original single form --}}
```

Remove it entirely — it is now replaced by the individual step divs in Tasks 2–4.

- [ ] **Step 2: Run full test suite to confirm nothing broken**

```bash
php artisan test --compact tests/Feature/HostedDonationFormTest.php
```

Expected: All PASS.

- [ ] **Step 3: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/donation-form.blade.php
git commit -m "chore: remove old single-form block after multi-step checkout migration"
```

---

## Task 8: Push to GitHub

- [ ] **Step 1: Push branch**

```bash
git push origin feature/stripe-integration
```

---

## Spec Coverage Check

| Spec requirement | Task |
|-----------------|------|
| 3 steps: Amount → Details → Payment | Tasks 2, 3, 4 |
| Text-only progress: "Step X of 3 — [name]" | Task 2 (added to each step div) |
| Thank you screen with donor name + email | Task 5 |
| Back navigation on Step 2 and 3 | Tasks 3, 4 |
| Mobile: hide left panel | Task 6 |
| Alpine.js step state (`currentStep`, `stepErrors`) | Task 1 |
| Client-side validation before step transition | Task 1 |
| Error screen → "Try again" returns to Step 3 | Task 5 |
| Summary bar on Step 3 (amount + frequency) | Task 4 |
