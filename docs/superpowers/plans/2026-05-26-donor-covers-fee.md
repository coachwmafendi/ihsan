# Donor Covers Processing Fee — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let donors opt to cover the Stripe processing fee so the organisation receives 100% of the intended donation amount.

**Architecture:** A `coverFee` boolean Livewire property drives a pre-checked checkbox in Step 1. A `#[Computed]` method estimates the Stripe fee (3% + RM 0.50). On `submit()`, `donor_fee_covered` is stored on the donation record; `CreatePaymentIntent` is updated to charge `gross_amount + donor_fee_covered`. The toggle is per-campaign via Element config JSON.

**Tech Stack:** Laravel 13, Livewire 4, Filament 5, Stripe PHP SDK, Pest 4

---

## File Map

| Action | File |
|---|---|
| Create | `database/migrations/2026_05_26_XXXXXX_add_donor_fee_covered_to_donations_table.php` |
| Modify | `app/Models/Donation.php` |
| Modify | `app/Livewire/DonationForm.php` |
| Modify | `app/Actions/Stripe/CreatePaymentIntent.php` |
| Modify | `app/Filament/App/Resources/Elements/Schemas/ElementForm.php` |
| Modify | `resources/views/livewire/donation-form.blade.php` |
| Create | `tests/Feature/DonorCoversFeatureTest.php` |

---

## Task 1: Migration — add `donor_fee_covered` column

**Files:**
- Create: `database/migrations/2026_05_26_XXXXXX_add_donor_fee_covered_to_donations_table.php`

- [ ] **Step 1: Generate migration**

```bash
php artisan make:migration add_donor_fee_covered_to_donations_table --no-interaction
```

- [ ] **Step 2: Write migration**

Open the generated file and replace its contents:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->decimal('donor_fee_covered', 12, 2)->default(0)->after('stripe_fee');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('donor_fee_covered');
        });
    }
};
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate --no-interaction
```

Expected: `Migrating: 2026_05_26_XXXXXX_add_donor_fee_covered_to_donations_table` then `Migrated`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat(donations): add donor_fee_covered column"
```

---

## Task 2: Donation model — register new column

**Files:**
- Modify: `app/Models/Donation.php`

- [ ] **Step 1: Add `donor_fee_covered` to `$fillable`**

In `app/Models/Donation.php`, the `#[Fillable([...])]` attribute lists column names. Add `'donor_fee_covered'` after `'stripe_fee'`:

```php
#[Fillable(['campaign_id', 'donor_id', 'subscription_id', 'stripe_payment_intent_id', 'stripe_charge_id', 'payment_method_brand', 'payment_method_type', 'donor_country', 'gross_amount', 'stripe_fee', 'donor_fee_covered', 'processing_fee', 'net_amount', 'currency', 'base_currency', 'base_amount', 'status', 'type', 'donor_message', 'is_anonymous', 'utm_params', 'invoice_number'])]
```

- [ ] **Step 2: Add cast**

In the `$casts` array (look for `'stripe_fee' => 'decimal:2'`), add:

```php
'donor_fee_covered' => 'decimal:2',
```

- [ ] **Step 3: Run pint**

```bash
vendor/bin/pint app/Models/Donation.php --format agent
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/Donation.php
git commit -m "feat(donation): register donor_fee_covered in model"
```

---

## Task 3: Write failing tests first

**Files:**
- Create: `tests/Feature/DonorCoversFeatureTest.php`

- [ ] **Step 1: Create test file**

```bash
php artisan make:test --pest DonorCoversFeatureTest --no-interaction
```

- [ ] **Step 2: Write all tests**

Replace the contents of `tests/Feature/DonorCoversFeatureTest.php`:

```php
<?php

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Organization;
use Livewire\Livewire;
use Stripe\PaymentIntent;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()
        ->for($this->organization)
        ->create(['status' => CampaignStatus::Active]);
    $this->element = Element::factory()
        ->for($this->organization)
        ->for($this->campaign)
        ->create([
            'type' => ElementType::Form,
            'config' => [
                'allow_monthly' => true,
                'allow_cover_fee' => true,
            ],
        ]);
});

it('shows cover fee checkbox when allow_cover_fee is true', function () {
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('cover the processing fee', false);
});

it('hides cover fee checkbox when allow_cover_fee is false', function () {
    $this->element->update(['config' => ['allow_monthly' => true, 'allow_cover_fee' => false]]);

    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertDontSee('cover the processing fee', false);
});

it('calculates estimated fee correctly', function () {
    $component = Livewire::test(DonationForm::class, ['element' => $this->element]);

    $component->set('amount', 100);
    expect($component->get('estimatedFee'))->toBe(3.5);

    $component->set('amount', 200);
    expect($component->get('estimatedFee'))->toBe(6.5);

    $component->set('amount', 1);
    expect($component->get('estimatedFee'))->toBe(0.53);
});

it('estimated fee is zero when cover fee is unchecked', function () {
    $component = Livewire::test(DonationForm::class, ['element' => $this->element]);
    $component->set('amount', 200)->set('coverFee', false);

    expect($component->get('estimatedFee'))->toBe(0.0);
});

it('estimated fee is zero when allow_cover_fee config is false', function () {
    $this->element->update(['config' => ['allow_monthly' => true, 'allow_cover_fee' => false]]);
    $component = Livewire::test(DonationForm::class, ['element' => $this->element]);
    $component->set('amount', 200)->set('coverFee', true);

    expect($component->get('estimatedFee'))->toBe(0.0);
});

it('charges gross_amount plus fee to stripe when donor covers fee', function () {
    $mockPaymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_coverfee',
        'client_secret' => 'pi_test_coverfee_secret',
        'status' => 'requires_payment_method',
        'amount' => 20650,
        'currency' => 'myr',
    ]);

    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->once()
        ->withArgs(function (Donation $donation) {
            return $donation->gross_amount == 200
                && $donation->donor_fee_covered == 6.50;
        })
        ->andReturn($mockPaymentIntent);

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 200)
        ->set('coverFee', true)
        ->set('frequency', 'one_time')
        ->set('name', 'Ahmad Donor')
        ->set('email', 'ahmad@example.com')
        ->call('submit');

    $donation = Donation::latest()->first();
    expect($donation->gross_amount)->toBe('200.00');
    expect($donation->donor_fee_covered)->toBe('6.50');
});

it('stores zero donor_fee_covered when donor opts out', function () {
    $mockPaymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_nocover',
        'client_secret' => 'pi_test_nocover_secret',
        'status' => 'requires_payment_method',
        'amount' => 20000,
        'currency' => 'myr',
    ]);

    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->once()
        ->andReturn($mockPaymentIntent);

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 200)
        ->set('coverFee', false)
        ->set('frequency', 'one_time')
        ->set('name', 'Ahmad Donor')
        ->set('email', 'ahmad@example.com')
        ->call('submit');

    $donation = Donation::latest()->first();
    expect($donation->donor_fee_covered)->toBe('0.00');
});
```

- [ ] **Step 3: Run tests — expect failures**

```bash
php artisan test --compact --filter=DonorCoversFeatureTest
```

Expected: multiple FAILs — `coverFee` property and `estimatedFee` don't exist yet.

- [ ] **Step 4: Commit failing tests**

```bash
git add tests/Feature/DonorCoversFeatureTest.php
git commit -m "test(donor-covers-fee): add failing feature tests"
```

---

## Task 4: DonationForm — add `coverFee` property and `estimatedFee` computed

**Files:**
- Modify: `app/Livewire/DonationForm.php`

- [ ] **Step 1: Add `Computed` import**

At the top of `DonationForm.php`, add to the existing `use` block:

```php
use Livewire\Attributes\Computed;
```

- [ ] **Step 2: Add `$coverFee` public property**

After the `public string $currency = 'myr';` line (~line 51), add:

```php
public bool $coverFee = true;
```

- [ ] **Step 3: Add `estimatedFee()` computed method**

Add this method after the `config()` method (around line 334):

```php
#[Computed]
public function estimatedFee(): float
{
    if (! $this->coverFee || ! $this->config('allow_cover_fee', true)) {
        return 0.0;
    }

    return round((float) $this->amount * 0.03 + 0.50, 2);
}
```

- [ ] **Step 4: Update `submit()` — store `donor_fee_covered`**

In the `Donation::query()->create([...])` call (around line 237), add `donor_fee_covered` after `stripe_fee`:

```php
$donation = Donation::query()->create([
    'campaign_id' => $campaignId,
    'donor_id' => $donor->getKey(),
    'gross_amount' => $validated['amount'],
    'stripe_fee' => 0,
    'donor_fee_covered' => $this->estimatedFee,
    'processing_fee' => 0,
    'net_amount' => $validated['amount'],
    'currency' => $this->currency,
    'status' => DonationStatus::Pending,
    'type' => $validated['frequency'] === 'monthly' ? DonationType::Recurring : DonationType::OneTime,
    'donor_message' => filled($validated['comment'] ?? null) ? $validated['comment'] : null,
    'is_anonymous' => false,
    'utm_params' => $utmParams,
]);
```

- [ ] **Step 5: Run pint**

```bash
vendor/bin/pint app/Livewire/DonationForm.php --format agent
```

- [ ] **Step 6: Run tests**

```bash
php artisan test --compact --filter=DonorCoversFeatureTest
```

Expected: `calculates estimated fee correctly`, `estimated fee is zero` tests should now PASS. Stripe-related tests still FAIL (CreatePaymentIntent not updated yet).

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/DonationForm.php
git commit -m "feat(donation-form): add coverFee property and estimatedFee computed"
```

---

## Task 5: CreatePaymentIntent — charge gross + fee

**Files:**
- Modify: `app/Actions/Stripe/CreatePaymentIntent.php`

- [ ] **Step 1: Update amount calculation**

In `CreatePaymentIntent::create()`, find:

```php
'amount' => (int) ((float) $donation->gross_amount * 100),
```

Replace with:

```php
'amount' => (int) (((float) $donation->gross_amount + (float) $donation->donor_fee_covered) * 100),
```

- [ ] **Step 2: Run pint**

```bash
vendor/bin/pint app/Actions/Stripe/CreatePaymentIntent.php --format agent
```

- [ ] **Step 3: Run tests**

```bash
php artisan test --compact --filter=DonorCoversFeatureTest
```

Expected: all tests PASS.

- [ ] **Step 4: Run full test suite to check for regressions**

```bash
php artisan test --compact
```

Expected: all existing tests still pass.

- [ ] **Step 5: Commit**

```bash
git add app/Actions/Stripe/CreatePaymentIntent.php
git commit -m "feat(stripe): charge gross_amount + donor_fee_covered to Stripe"
```

---

## Task 6: ElementForm — add `allow_cover_fee` toggle

**Files:**
- Modify: `app/Filament/App/Resources/Elements/Schemas/ElementForm.php`

- [ ] **Step 1: Add toggle after `allow_monthly`**

Find the `Toggle::make('allow_monthly')` block (around line 502). Add the new toggle immediately after its closing parenthesis:

```php
Toggle::make('allow_monthly')
    ->label('Allow monthly donations')
    ->default(true)
    ->live(),
Toggle::make('allow_cover_fee')
    ->label('Allow donors to cover processing fee')
    ->helperText('Donors will see a pre-checked option to cover the Stripe processing fee (~3% + RM 0.50).')
    ->default(true)
    ->live(),
```

- [ ] **Step 2: Add `allow_cover_fee` to the config keys allowlist**

Find the `foreach ([...] as $key)` array (around line 700). Add `'allow_cover_fee'` to the list:

```php
'suggested_amounts_monthly', 'show_suggested', 'display_as_popup', 'allow_cover_fee',
```

- [ ] **Step 3: Run pint**

```bash
vendor/bin/pint app/Filament/App/Resources/Elements/Schemas/ElementForm.php --format agent
```

- [ ] **Step 4: Run full test suite**

```bash
php artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/App/Resources/Elements/Schemas/ElementForm.php
git commit -m "feat(element-form): add allow_cover_fee toggle to campaign settings"
```

---

## Task 7: Blade view — add cover fee checkbox UI

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php`

- [ ] **Step 1: Add checkbox between amount input and Continue button**

Find the amount input wrapper div in Step 1 (look for `min-h-14 items-center rounded-xl border`). After the closing `</div>` of that wrapper and before the `stepErrors.amount` div, add:

```blade
@if ($this->config('allow_cover_fee', true))
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-slate-300 hover:bg-slate-100">
        <input
            type="checkbox"
            wire:model.live="coverFee"
            class="mt-0.5 size-4 cursor-pointer rounded border-slate-300 text-teal-600 accent-teal-600"
        />
        <span class="flex flex-col gap-0.5">
            <span class="text-sm font-medium text-slate-700">
                I'll cover the processing fee
                <span class="text-teal-700">(+{{ $currencySymbol }}{{ number_format($this->estimatedFee, 2) }})</span>
            </span>
            <span class="text-xs text-slate-400">Help ensure 100% of your donation reaches us.</span>
        </span>
    </label>
@endif
```

Placement — it should appear between the amount input row and the `stepErrors.amount` div:

```blade
{{-- amount input div --}}
<div class="flex min-h-14 ...">...</div>

{{-- cover fee checkbox — INSERT HERE --}}
@if ($this->config('allow_cover_fee', true))
    ...
@endif

<div x-show="stepErrors.amount" ...></div>
```

- [ ] **Step 2: Verify in browser**

Visit `https://ihsan.test/donate/{token}` and confirm:
- Checkbox appears below amount input, pre-checked
- `+RM X.XX` amount updates when amount changes
- Unchecking removes the fee display (amount goes to 0.00)

- [ ] **Step 3: Run full test suite**

```bash
php artisan test --compact --filter=DonorCoversFeatureTest
```

Expected: all tests pass including `shows cover fee checkbox` and `hides cover fee checkbox`.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/donation-form.blade.php
git commit -m "feat(donation-form): add donor covers fee checkbox to Step 1"
```

---

## Task 8: Final verification

- [ ] **Step 1: Run full test suite**

```bash
php artisan test --compact
```

Expected: all tests pass, no regressions.

- [ ] **Step 2: Run pint on all dirty files**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Manual smoke test**

1. Go to `https://ihsan.test/donate/{any-token}`
2. Confirm checkbox visible, pre-checked
3. Change amount — fee amount updates reactively
4. Uncheck — fee shows RM 0.00
5. Re-check — fee returns
6. Go to campaign element settings in admin — confirm `allow_cover_fee` toggle exists
7. Set `allow_cover_fee = false` — revisit form, confirm checkbox absent

- [ ] **Step 4: Push**

```bash
git push
```
