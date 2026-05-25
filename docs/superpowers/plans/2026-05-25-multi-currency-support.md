# Multi-Currency Support Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Accept donations in MYR, USD, SGD with org-level currency settings, per-currency suggested amounts, and MYR-normalized accounting via Stripe exchange rates.

**Architecture:** Org settings (`accepted_currencies` in JSON) → campaign suggested amounts per currency → donation form detects/selects currency → Stripe handles multi-currency natively → post-payment sync extracts exchange rate for MYR base_amount.

**Tech Stack:** Laravel 13, Livewire 4, Filament 5, Stripe, MySQL (prod) / SQLite (tests), Alpine.js

---

### Task 1: Migration — add base_currency & base_amount to donations table

**Files:**
- Create: `database/migrations/2026_05_25_000001_add_base_currency_to_donations_table.php`
- Modify: `app/Models/Donation.php`

- [ ] **Step 1: Create migration**

Run: `php artisan make:migration add_base_currency_to_donations_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('base_currency', 5)->nullable()->after('currency');
            $table->decimal('base_amount', 12, 2)->nullable()->after('base_currency');
        });

        // Backfill existing rows — all are MYR
        DB::table('donations')->whereNull('base_currency')->update([
            'base_currency' => 'myr',
            'base_amount' => DB::raw('gross_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['base_currency', 'base_amount']);
        });
    }
};
```

- [ ] **Step 2: Update Donation model fillable + casts**

```php
// app/Models/Donation.php
// Add to #[Fillable] attribute — append to existing:
'base_currency', 'base_amount'

// Update casts method to add:
'base_currency' => 'string',
'base_amount' => 'decimal:2',
```

- [ ] **Step 3: Run migration**

Run: `php artisan migrate`

Expected: `Migration created successfully.` and donations backfilled.

---

### Task 2: Org settings — add currency checkboxes to Pembayaran page

**Files:**
- Modify: `app/Filament/App/Pages/Pembayaran.php`
- Modify: `resources/views/filament/app/pages/pembayaran.blade.php`

- [ ] **Step 1: Add `$currencies` property and helper methods to Pembayaran page**

```php
// app/Filament/App/Pages/Pembayaran.php

// Add imports at top:
use Filament\Notifications\Notification;

// Add new properties:
public array $currencies = ['myr' => true, 'usd' => false, 'sgd' => false];

// Add mount method:
public function mount(): void
{
    $org = auth()->user()->organization;
    $settings = $org?->settings ?? [];
    $accepted = $settings['accepted_currencies'] ?? ['myr'];

    $this->currencies = [
        'myr' => true,
        'usd' => in_array('usd', $accepted),
        'sgd' => in_array('sgd', $accepted),
    ];
}

// Add updated method for auto-save:
public function updatedCurrencies(): void
{
    $org = auth()->user()->organization;
    if ($org === null) {
        return;
    }

    // Build the list of enabled currencies (MYR always included)
    $accepted = ['myr'];
    if ($this->currencies['usd']) {
        $accepted[] = 'usd';
    }
    if ($this->currencies['sgd']) {
        $accepted[] = 'sgd';
    }

    $settings = array_merge($org->settings ?? [], ['accepted_currencies' => $accepted]);
    $org->update(['settings' => $settings]);

    Notification::make()
        ->title('Mata wang diterima dikemas kini')
        ->success()
        ->send();
}
```

- [ ] **Step 2: Add currency section to blade view**

Add after the existing Stripe Connect section in `resources/views/filament/app/pages/pembayaran.blade.php`:

```blade
<x-filament::section icon="heroicon-o-banknotes">
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <span>Mata Wang Diterima</span>
        </div>
    </x-slot>

    <div class="space-y-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Pilih mata wang yang boleh digunakan penderma untuk membuat derma.
            Ringgit Malaysia (MYR) sentiasa diaktifkan.
        </p>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <input
                        type="checkbox"
                        wire:model.live="currencies.myr"
                        checked
                        disabled
                        class="size-4 rounded border-gray-300 text-teal-600 focus:ring-teal-600 dark:border-gray-600 dark:bg-gray-800"
                    />
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">MYR</p>
                        <p class="text-xs text-gray-500">Ringgit Malaysia</p>
                    </div>
                </label>
            </div>

            <div>
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <input
                        type="checkbox"
                        wire:model.live="currencies.usd"
                        class="size-4 rounded border-gray-300 text-teal-600 focus:ring-teal-600 dark:border-gray-600 dark:bg-gray-800"
                    />
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">USD</p>
                        <p class="text-xs text-gray-500">US Dollar</p>
                    </div>
                </label>
            </div>

            <div>
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <input
                        type="checkbox"
                        wire:model.live="currencies.sgd"
                        class="size-4 rounded border-gray-300 text-teal-600 focus:ring-teal-600 dark:border-gray-600 dark:bg-gray-800"
                    />
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">SGD</p>
                        <p class="text-xs text-gray-500">Singapore Dollar</p>
                    </div>
                </label>
            </div>
        </div>
    </div>
</x-filament::section>
```

---

### Task 3: Restructure suggested amounts to per-currency format

**Files:**
- Modify: `app/Filament/Forms/Components/SuggestedAmounts.php`
- Modify: `resources/views/filament/forms/components/suggested-amounts.blade.php`
- Modify: `app/Livewire/DonationForm.php`

- [ ] **Step 1: Update SuggestedAmounts defaults + normalization to per-currency**

```php
// app/Filament/Forms/Components/SuggestedAmounts.php
// Replace the setUp() method:

protected function setUp(): void
{
    parent::setUp();

    $this->default([
        'myr' => [
            'one_time' => [
                ['amount' => '30', 'label' => ''],
                ['amount' => '50', 'label' => ''],
                ['amount' => '100', 'label' => ''],
                ['amount' => '200', 'label' => ''],
                ['amount' => '500', 'label' => ''],
                ['amount' => '1000', 'label' => ''],
            ],
            'monthly' => [
                ['amount' => '200', 'label' => ''],
                ['amount' => '100', 'label' => ''],
                ['amount' => '50', 'label' => ''],
                ['amount' => '30', 'label' => ''],
                ['amount' => '10', 'label' => ''],
                ['amount' => '5', 'label' => ''],
            ],
            'default_monthly' => '25',
        ],
        'usd' => [
            'one_time' => [
                ['amount' => '10', 'label' => ''],
                ['amount' => '20', 'label' => ''],
                ['amount' => '50', 'label' => ''],
                ['amount' => '100', 'label' => ''],
                ['amount' => '250', 'label' => ''],
                ['amount' => '500', 'label' => ''],
            ],
            'monthly' => [
                ['amount' => '50', 'label' => ''],
                ['amount' => '25', 'label' => ''],
                ['amount' => '10', 'label' => ''],
                ['amount' => '5', 'label' => ''],
                ['amount' => '2', 'label' => ''],
                ['amount' => '1', 'label' => ''],
            ],
            'default_monthly' => '10',
        ],
        'sgd' => [
            'one_time' => [
                ['amount' => '10', 'label' => ''],
                ['amount' => '20', 'label' => ''],
                ['amount' => '50', 'label' => ''],
                ['amount' => '100', 'label' => ''],
                ['amount' => '250', 'label' => ''],
                ['amount' => '500', 'label' => ''],
            ],
            'monthly' => [
                ['amount' => '50', 'label' => ''],
                ['amount' => '25', 'label' => ''],
                ['amount' => '10', 'label' => ''],
                ['amount' => '5', 'label' => ''],
                ['amount' => '2', 'label' => ''],
                ['amount' => '1', 'label' => ''],
            ],
            'default_monthly' => '10',
        ],
        'impact_enabled' => false,
    ]);

    $this->afterStateHydrated(function (SuggestedAmounts $component, $state) {
        if (! is_array($state)) {
            return;
        }

        // If old format {one_time: [...], monthly: [...]} — wrap into myr key
        if (isset($state['one_time']) && ! isset($state['myr'])) {
            $state = [
                'myr' => [
                    'one_time' => $state['one_time'],
                    'monthly' => $state['monthly'],
                    'default_monthly' => $state['default_monthly'] ?? '25',
                ],
                'usd' => null,
                'sgd' => null,
                'impact_enabled' => $state['impact_enabled'] ?? false,
            ];
            $component->state($state);
            return;
        }

        // If legacy flat array — wrap into myr key
        if (! isset($state['myr'])) {
            $flatAmounts = array_filter(array_values($state), fn ($v) => is_numeric($v) && $v > 0);
            if (! empty($flatAmounts)) {
                $oneTime = array_map(fn ($v) => ['amount' => (string) $v, 'label' => ''], $flatAmounts);
                $state = [
                    'myr' => [
                        'one_time' => $oneTime,
                        'monthly' => $oneTime,
                        'default_monthly' => '25',
                    ],
                    'usd' => null,
                    'sgd' => null,
                    'impact_enabled' => false,
                ];
                $component->state($state);
            }
        }
    });
}
```

- [ ] **Step 2: Update the Blade view for per-currency editing**

Replace `resources/views/filament/forms/components/suggested-amounts.blade.php` with a version that adds a currency row of tabs above the frequency tabs:

```blade
@php
    $statePath = $getStatePath();
    $currencyLabels = ['myr' => 'RM (MYR)', 'usd' => '$ (USD)', 'sgd' => 'S$ (SGD)'];
    $currencySymbols = ['myr' => 'RM', 'usd' => '$', 'sgd' => 'S$'];
@endphp

<div
    x-data="{
        activeCurrency: 'myr',
        activeTab: 'monthly',
        state: $wire.$entangle('{{ $statePath }}'),
        get currencyData() {
            return this.state?.[this.activeCurrency] || { one_time: [], monthly: [], default_monthly: '' };
        },
        set currencyData(value) {
            this.state = { ...this.state, [this.activeCurrency]: value };
        },
        get oneTimeAmounts() {
            return this.currencyData?.one_time || [];
        },
        set oneTimeAmounts(value) {
            this.currencyData = { ...this.currencyData, one_time: value };
        },
        get monthlyAmounts() {
            return this.currencyData?.monthly || [];
        },
        set monthlyAmounts(value) {
            this.currencyData = { ...this.currencyData, monthly: value };
        },
        get defaultMonthly() {
            return this.currencyData?.default_monthly || '';
        },
        set defaultMonthly(value) {
            this.currencyData = { ...this.currencyData, default_monthly: value };
        },
        get currentAmounts() {
            return this.activeTab === 'one-time' ? this.oneTimeAmounts : this.monthlyAmounts;
        },
        set currentAmounts(value) {
            if (this.activeTab === 'one-time') {
                this.oneTimeAmounts = value;
            } else {
                this.monthlyAmounts = value;
            }
        },
        updateAmount(index, value) {
            const amounts = [...this.currentAmounts];
            if (amounts[index]) {
                amounts[index].amount = value.replace(/[^0-9.]/g, '');
                this.currentAmounts = amounts;
            }
        },
        addAmount() {
            const amounts = [...this.currentAmounts, { amount: '', label: '' }];
            this.currentAmounts = amounts;
        },
        removeAmount(index) {
            const amounts = [...this.currentAmounts];
            amounts.splice(index, 1);
            this.currentAmounts = amounts;
        }
    }"
    class="suggested-amounts-component"
>
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm ring-1 ring-zinc-950/5 dark:border-white/10 dark:bg-zinc-900 dark:ring-white/10">
        <div class="border-b border-zinc-200 bg-zinc-50/80 px-4 py-5 dark:border-white/10 dark:bg-white/5 sm:px-6">
            <div class="space-y-4">
                <div class="mx-auto max-w-lg space-y-1 text-center">
                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">Pra-set kekerapan & mata wang</p>
                    <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                        Tetapkan butang derma untuk setiap mata wang yang diterima.
                    </p>
                </div>

                {{-- Currency tabs --}}
                <div class="mx-auto grid w-full max-w-sm grid-cols-3 gap-1 rounded-lg bg-zinc-200/70 p-1 dark:bg-zinc-800" role="tablist" aria-label="Currency">
                    @foreach (['myr' => 'RM (MYR)', 'usd' => '$ (USD)', 'sgd' => 'S$ (SGD)'] as $code => $label)
                        <button
                            type="button"
                            role="tab"
                            @click="activeCurrency = '{{ $code }}'"
                            :aria-selected="activeCurrency === '{{ $code }}'"
                            :class="activeCurrency === '{{ $code }}' ? 'bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white'"
                            class="rounded-md px-4 py-2 text-sm font-semibold transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Frequency tabs --}}
                <div class="mx-auto grid w-full max-w-xs grid-cols-2 gap-1 rounded-lg bg-zinc-200/70 p-1 dark:bg-zinc-800" role="tablist" aria-label="Suggested amount frequency">
                    <button
                        type="button" role="tab"
                        @click="activeTab = 'one-time'"
                        :aria-selected="activeTab === 'one-time'"
                        :class="activeTab === 'one-time' ? 'bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white'"
                        class="rounded-md px-4 py-2 text-sm font-semibold transition"
                    >
                        Sekali sahaja
                    </button>
                    <button
                        type="button" role="tab"
                        @click="activeTab = 'monthly'"
                        :aria-selected="activeTab === 'monthly'"
                        :class="activeTab === 'monthly' ? 'bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-white' : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white'"
                        class="rounded-md px-4 py-2 text-sm font-semibold transition"
                    >
                        Bulanan
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-6 p-4 sm:p-6">
            <div class="mx-auto max-w-xl space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-zinc-950 dark:text-white">Jumlah pra-set</h4>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400" x-text="activeTab === 'monthly' ? 'Dipaparkan apabila penyokong memilih derma bulanan.' : 'Dipaparkan apabila penyokong memilih derma satu masa.'"></p>
                    </div>
                    <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400" x-text="(activeTab === 'one-time' ? oneTimeAmounts.length : monthlyAmounts.length) + ' pilihan'"></span>
                </div>

                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                    <template x-for="(amount, index) in currentAmounts" :key="`${activeCurrency}-${activeTab}-${index}`">
                        <div class="group relative rounded-lg border border-zinc-200 bg-zinc-50/60 p-3 transition hover:border-zinc-300 focus-within:border-primary-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-white/10 dark:bg-white/5 dark:hover:border-white/20 dark:focus-within:bg-zinc-950">
                            <button
                                type="button"
                                @click="removeAmount(index)"
                                class="absolute -right-1.5 -top-1.5 hidden size-5 items-center justify-center rounded-full bg-red-500 text-white shadow-sm transition hover:bg-red-600 group-hover:flex dark:bg-red-600 dark:hover:bg-red-500"
                                aria-label="Buang jumlah"
                            >
                                <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            <span class="mb-2 block text-xs font-medium text-zinc-500 dark:text-zinc-400" x-text="'Pilihan ' + (index + 1)"></span>
                            <span class="flex min-h-11 items-center rounded-md border border-zinc-200 bg-white shadow-xs transition group-focus-within:border-primary-500 dark:border-white/10 dark:bg-zinc-900">
                                <span class="flex h-full items-center border-r border-zinc-200 px-3 text-sm font-semibold text-zinc-500 dark:border-white/10 dark:text-zinc-400">
                                    <span x-text="activeCurrency === 'myr' ? 'RM' : (activeCurrency === 'usd' ? '$' : 'S$')"></span>
                                </span>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    :value="amount.amount || ''"
                                    @input="updateAmount(index, $event.target.value)"
                                    :aria-label="'Suggested amount option ' + (index + 1)"
                                    placeholder="0"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-base font-semibold text-zinc-950 placeholder-zinc-400 outline-none focus:ring-0 dark:text-white dark:placeholder-zinc-500"
                                >
                            </span>
                        </div>
                    </template>
                </div>

                <button
                    type="button"
                    @click="addAmount()"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-zinc-300 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-primary-400 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-white/20 dark:text-zinc-400 dark:hover:border-primary-500 dark:hover:text-primary-400"
                >
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Tambah jumlah
                </button>
            </div>

            <div
                class="mx-auto max-w-xl rounded-lg border border-primary-200 bg-primary-50/70 p-4 dark:border-primary-500/20 dark:bg-primary-500/10"
                x-show="activeTab === 'monthly'"
                x-cloak
            >
                <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div class="space-y-1">
                        <label class="text-sm font-semibold text-zinc-950 dark:text-white" :for="'suggested-amounts-default-monthly-' + activeCurrency">
                            Bulanan lalai
                        </label>
                        <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                            Penderma lihat jumlah ini sebagai pra-pilih untuk derma bulanan.
                        </p>
                    </div>

                    <div class="flex min-h-11 w-full items-center rounded-md border border-primary-200 bg-white shadow-xs focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-primary-500/30 dark:bg-zinc-950 lg:w-44">
                        <span class="flex h-full items-center border-r border-primary-200 px-3 text-sm font-semibold text-primary-700 dark:border-primary-500/30 dark:text-primary-300">
                            <span x-text="activeCurrency === 'myr' ? 'RM' : (activeCurrency === 'usd' ? '$' : 'S$')"></span>
                        </span>
                        <input
                            :id="'suggested-amounts-default-monthly-' + activeCurrency"
                            type="text"
                            inputmode="numeric"
                            :value="defaultMonthly || ''"
                            @input="defaultMonthly = $event.target.value.replace(/[^0-9.]/g, '')"
                            placeholder="0"
                            class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-base font-semibold text-zinc-950 placeholder-zinc-400 outline-none focus:ring-0 dark:text-white dark:placeholder-zinc-500"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Update DonationForm::suggestedAmounts() to read per-currency format**

```php
// app/Livewire/DonationForm.php
// Add a $currency property:
public string $currency = 'myr';

// Replace the suggestedAmounts() method:
public function suggestedAmounts(?string $frequency = null): array
{
    $frequency ??= $this->frequency;

    $campaign = $this->element?->campaign ?? $this->campaign;

    if (! $campaign) {
        return [];
    }

    $amounts = $campaign->suggested_amounts;

    // New per-currency format: {myr: {one_time: [...], monthly: [...]}}
    if (is_array($amounts) && isset($amounts[$this->currency])) {
        $currencyAmounts = $amounts[$this->currency];
        if (is_array($currencyAmounts) && isset($currencyAmounts[$frequency])) {
            $amounts = $currencyAmounts[$frequency];
        } else {
            $amounts = [];
        }
    } elseif (is_array($amounts) && isset($amounts[$frequency])) {
        // Old format {one_time: [...], monthly: [...]} — backward compat
        $amounts = $amounts[$frequency];
    } else {
        $amounts = $campaign->{'suggested_amounts_'.$frequency};
    }

    if (! is_array($amounts) || $amounts === []) {
        $amounts = $this->config('suggested_amounts_'.$frequency);
    }

    if (! is_array($amounts) || $amounts === []) {
        $amounts = $this->config('suggested_amounts');
    }

    if (! is_array($amounts) || $amounts === []) {
        $amounts = [200, 100, 50, 30, 10, 5];
    }

    return collect($amounts)
        ->map(fn (mixed $amount): int => (int) (is_array($amount) ? ($amount['amount'] ?? 0) : $amount))
        ->filter(fn (int $amount): bool => $amount > 0)
        ->unique()
        ->values()
        ->all();
}
```

---

### Task 4: Currency detection + donation form UI for multi-currency

**Files:**
- Modify: `app/Livewire/DonationForm.php`
- Modify: `resources/views/livewire/donation-form.blade.php`

- [ ] **Step 1: Add currency property + initialization to DonationForm**

Add property:
```php
public string $currency = 'myr';
```

Update `mount()` — detect currency from org settings and browser locale. Add after the existing lines where `$this->isPopup` is set:

```php
// Currency detection — read accepted currencies from org
$acceptedCurrencies = $this->getAcceptedCurrencies();

// Try to detect from browser (done in JS), default to MYR
$this->currency = in_array('myr', $acceptedCurrencies) ? 'myr' : ($acceptedCurrencies[0] ?? 'myr');

// The first suggested amount should match the detected currency
$amounts = $this->suggestedAmounts();
$this->amount = $this->config('default_amount', $amounts[0] ?? 5);
```

Add new helper method:
```php
/**
 * @return array<int, string>
 */
public function getAcceptedCurrencies(): array
{
    $organization = $this->element?->campaign?->organization ?? $this->campaign?->organization;

    if ($organization === null) {
        return ['myr'];
    }

    return $organization->settings['accepted_currencies'] ?? ['myr'];
}

public function selectCurrency(string $currency): void
{
    $accepted = $this->getAcceptedCurrencies();
    if (! in_array($currency, $accepted, true)) {
        return;
    }

    $this->currency = $currency;
    $amounts = $this->suggestedAmounts();
    $this->amount = $amounts[0] ?? 5;
}
```

Update `submit()` — change `'currency' => 'myr'` to `'currency' => $this->currency`.

Update `rules()` — add currency validation:
```php
'currency' => ['required', 'string', 'in:myr,usd,sgd'],
```

- [ ] **Step 2: Update Blade view for currency selector + dynamic symbols**

In `resources/views/livewire/donation-form.blade.php`:

Add a currency selector after the frequency buttons (before suggested amounts):

```blade
{{-- Currency selector --}}
@if (count($this->getAcceptedCurrencies()) > 1)
    <div class="flex gap-1 rounded-lg bg-slate-100 p-0.5">
        @php $currencyLabels = ['myr' => 'RM', 'usd' => '$', 'sgd' => 'S$']; @endphp
        @foreach ($this->getAcceptedCurrencies() as $code)
            <button
                type="button"
                wire:click="selectCurrency('{{ $code }}')"
                class="flex-1 rounded-md px-3 py-1.5 text-xs font-semibold transition"
                style="{{ $currency === $code ? 'background-color: white; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: #64748b;' }}"
            >
                {{ $currencyLabels[$code] ?? strtoupper($code) }}
            </button>
        @endforeach
    </div>
@endif
```

Replace all hardcoded `RM` with dynamic currency symbols throughout the file. Key locations:

Line 89: `RM {{ number_format($collectedAmount, 2) }} raised`
Line 90: `Goal RM {{ number_format($targetAmount, 2) }}`
Line 132-133: same in embed section
Line 162-163: same in full page section
Line 225, 238: `RM {{ number_format($amount) }}`
Line 248: `RM` in amount input prefix
Line 256: `MYR` in amount input suffix
Line 365: `RM <span x-text="...">`

Add at top after `@php` block:
```php
$currencySymbols = ['myr' => 'RM', 'usd' => '$', 'sgd' => 'S$'];
$currencyCode = $this->currency;
$symbol = $currencySymbols[$currencyCode] ?? 'RM';
```

Replace all `RM` with `{{ $symbol }}` in amount display contexts.

Replace line 256 (`<span class="text-sm font-medium text-slate-500">MYR</span>`) with:
```blade
<span class="text-sm font-medium text-slate-500">{{ strtoupper($this->currency) }}</span>
```

- [ ] **Step 3: Add JS-based currency detection in the @script section**

In the Alpine component `init()`, add at the beginning:

```javascript
// Detect locale currency
const locale = Intl.DateTimeFormat().resolvedOptions().locale;
const countryCode = locale.split('-')[1]?.toLowerCase();
const currencyMap = { my: 'myr', us: 'usd', sg: 'sgd' };
const detected = currencyMap[countryCode] || 'myr';
const accepted = @json($this->getAcceptedCurrencies());
if (accepted.includes(detected) && detected !== 'myr') {
    $wire.selectCurrency(detected);
}
```

This should fire on page load, after mount. Need to use `$wire` proxy.

---

### Task 5: Update SyncDonationStripeDetails to extract exchange rate

**Files:**
- Modify: `app/Actions/Stripe/SyncDonationStripeDetails.php`

- [ ] **Step 1: Extract exchange rate and save base_currency/base_amount**

Update the `sync()` method — after the `$paymentIntent` is retrieved and before calling `$donation->update([...])`, add:

```php
// Extract exchange rate for base_amount calculation
$baseCurrency = 'myr';
$baseAmount = (float) $donation->gross_amount;

if (strtolower($donation->currency) !== 'myr') {
    $charge = $paymentIntent->latest_charge ?? ($paymentIntent->charges->data[0] ?? null);
    if (is_string($charge)) {
        try {
            $charge = Charge::retrieve([
                'id' => $charge,
                'expand' => ['balance_transaction'],
            ], $stripeOptions);
        } catch (\Exception $e) {
            $charge = null;
        }
    }

    if ($charge && $charge->balance_transaction) {
        $btId = is_string($charge->balance_transaction)
            ? $charge->balance_transaction
            : $charge->balance_transaction->id;

        try {
            $bt = BalanceTransaction::retrieve($btId, $stripeOptions);
            if ($bt->exchange_rate !== null) {
                $baseAmount = round(((float) $donation->gross_amount) * ((float) $bt->exchange_rate), 2);
            }
        } catch (\Exception $e) {
            // Fallback: use gross_amount as base (assume 1:1)
        }
    }
}
```

Add to the `$donation->update([...])` call:
```php
'base_currency' => $baseCurrency,
'base_amount' => $baseAmount,
```

Update `net_amount` calculation to use base_amount:
```php
'net_amount' => (float) $baseAmount - $stripeFee,
```

And update `processingFeePercent` usage on line 96 — change to use `$baseAmount`:
```php
$processingFee = round((float) $baseAmount * $this->processingFeePercent() / 100, 2);
```

Also add import for `Charge` at top:
```php
use Stripe\Charge;
```

- [ ] **Step 2: Update `$donation->gross_amount` reference in `net_amount`**

Change line 36 (`'net_amount' => (float) $donation->gross_amount - $stripeFee`) to:
```php
'net_amount' => (float) $baseAmount - $stripeFee,
```

---

### Task 6: Guard CreatePaymentIntent with supported currency check

**Files:**
- Modify: `app/Actions/Stripe/CreatePaymentIntent.php`

- [ ] **Step 1: Add supported currency validation**

Add at the beginning of `create()` method, after loading the relation:

```php
$supportedCurrencies = ['myr', 'usd', 'sgd'];
$currency = strtolower($donation->currency);

if (! in_array($currency, $supportedCurrencies, true)) {
    throw new \InvalidArgumentException("Unsupported currency: {$currency}");
}
```

Also verify org settings:
```php
$orgSettings = $organization->settings ?? [];
$acceptedCurrencies = $orgSettings['accepted_currencies'] ?? ['myr'];

if (! in_array($currency, $acceptedCurrencies, true)) {
    throw new \InvalidArgumentException("Currency {$currency} is not accepted by this organization.");
}
```

---

### Task 7: Update StripePaymentIntentController for multi-currency

**Files:**
- Modify: `app/Http/Controllers/StripePaymentIntentController.php`

- [ ] **Step 1: Read and validate currency from request**

Find where donation is created and change:
```php
'currency' => $request->input('currency', 'myr'),
```

Add validation rule:
```php
'currency' => ['sometimes', 'string', 'in:myr,usd,sgd'],
```

---

### Task 8: Write tests

**Files:**
- Modify: `tests/Feature/HostedDonationFormTest.php` (or create new test)

- [ ] **Step 1: Test donation form currency selection**

Add a test that exercises the multi-currency flow:

```php
it('creates donation with selected currency', function () {
    // Create org with MYR+USD accepted
    $organization = Organization::factory()
        ->create(['settings' => ['accepted_currencies' => ['myr', 'usd', 'sgd']]]);
    
    $campaign = Campaign::factory()
        ->for($organization)
        ->create([
            'suggested_amounts' => [
                'myr' => ['one_time' => [['amount' => '30']], 'monthly' => [['amount' => '5']], 'default_monthly' => '25'],
                'usd' => ['one_time' => [['amount' => '10']], 'monthly' => [['amount' => '2']], 'default_monthly' => '10'],
                'sgd' => ['one_time' => [['amount' => '10']], 'monthly' => [['amount' => '2']], 'default_monthly' => '10'],
            ],
        ]);

    $element = Element::factory()
        ->for($campaign)
        ->create(['type' => ElementType::Form, 'is_active' => true]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'myr')
        ->call('selectCurrency', 'usd')
        ->assertSet('currency', 'usd')
        ->assertSet('amount', 10);
});

it('rejects unsupported currency', function () {
    $organization = Organization::factory()
        ->create(['settings' => ['accepted_currencies' => ['myr']]]);
    
    $campaign = Campaign::factory()
        ->for($organization)
        ->create();

    $element = Element::factory()
        ->for($campaign)
        ->create(['type' => ElementType::Form, 'is_active' => true]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'myr')
        ->call('selectCurrency', 'usd')
        ->assertSet('currency', 'myr'); // Should not change
});

it('creates donation with correct currency in database', function () {
    // Setup organization with multi-currency
    $organization = Organization::factory()
        ->create(['settings' => ['accepted_currencies' => ['myr', 'usd']]]);

    $campaign = Campaign::factory()
        ->for($organization)
        ->create(['allow_recurring' => false]);

    $element = Element::factory()
        ->for($campaign)
        ->create(['type' => ElementType::Form, 'is_active' => true]);

    Stripe::fake();

    Livewire::test(DonationForm::class, ['element' => $element])
        ->set('currency', 'usd')
        ->set('amount', 50)
        ->set('frequency', 'one_time')
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '0123456789')
        ->call('submit');

    $this->assertDatabaseHas('donations', [
        'currency' => 'usd',
        'gross_amount' => 50.00,
    ]);
});
```

- [ ] **Step 2: Test SyncDonationStripeDetails exchange rate extraction**

```php
it('calculates base_amount from stripe exchange rate for non-MYR donations', function () {
    $organization = Organization::factory()->create(['stripe_onboarded' => false]);

    $campaign = Campaign::factory()
        ->for($organization)
        ->create();

    $donor = Donor::factory()->create();

    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'gross_amount' => 10.00,
        'currency' => 'usd',
        'stripe_payment_intent_id' => 'pi_mock_'.Str::random(12),
        'status' => DonationStatus::Pending,
    ]);

    // Mock Stripe PaymentIntent with balance_transaction having exchange_rate
    $balanceTransaction = Mockery::mock('stdClass');
    $balanceTransaction->exchange_rate = 4.45;
    $balanceTransaction->fee = 0;
    $balanceTransaction->fee_details = [];

    $charge = Mockery::mock('stdClass');
    $charge->id = 'ch_mock';
    $charge->balance_transaction = $balanceTransaction;

    $paymentIntent = Mockery::mock(StripePaymentIntent::class);
    $paymentIntent->latest_charge = $charge;
    $paymentIntent->payment_method = 'pm_mock';
    $paymentIntent->charges = (object) ['data' => [$charge]];
    $paymentIntent->id = $donation->stripe_payment_intent_id;

    // Mock the retrieve calls
    StripePaymentIntent::shouldReceive('retrieve')
        ->once()
        ->andReturn($paymentIntent);

    PaymentMethod::shouldReceive('retrieve')
        ->once()
        ->andReturn((object) ['type' => 'card', 'card' => (object) ['brand' => 'visa', 'country' => 'us']]);

    app(SyncDonationStripeDetails::class)->sync($donation, $paymentIntent);

    $donation->refresh();

    expect($donation->base_currency)->toBe('myr');
    expect((float) $donation->base_amount)->toBe(44.50); // 10.00 * 4.45
});
```

- [ ] **Step 3: Run tests**

Run: `php artisan test --compact --filter=Donation`

Expected: all existing tests pass + new tests pass.

---

### Task 9: Run Pint for code formatting

**Files:** — all PHP files modified above

- [ ] **Step 1: Run Pint**

Run: `vendor/bin/pint --format agent`

Expected: exits 0, files formatted per Laravel conventions.
