# Platform Fees & Monthly NGO Invoicing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop deducting platform fees per-transaction, record them, and bill NGOs monthly via Stripe Invoice.

**Architecture:** Remove `application_fee_amount` from Stripe PaymentIntent/Subscription creation. Create `PlatformFee` records automatically on successful donations. A monthly artisan command groups pending fees by NGO, creates Stripe Invoices, and sends them. Two new Filament admin pages expose fees and invoices with filtering & actions.

**Tech Stack:** Laravel 13, Filament 5, Stripe PHP SDK, SQLite

---

### Task 1: Migrations — monthly_invoices table & platform_fees update

**Files:**
- Create: `database/migrations/2026_05_23_080000_create_monthly_invoices_table.php`
- Create: `database/migrations/2026_05_23_081000_update_platform_fees_table.php`

- [ ] **Step 1: Create monthly_invoices migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_invoice_id')->unique();
            $table->string('invoice_number')->unique();
            $table->date('period');
            $table->decimal('total_fees', 12, 2);
            $table->string('stripe_status')->default('open')->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('stripe_invoice_url')->nullable();
            $table->string('stripe_invoice_pdf')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_invoices');
    }
};
```

- [ ] **Step 2: Create platform_fees update migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_fees', function (Blueprint $table) {
            $table->foreignId('monthly_invoice_id')->nullable()->constrained()->nullOnDelete()->after('status');
        });

        DB::statement("UPDATE platform_fees SET status = 'pending' WHERE status = 'pending'");
        DB::statement("UPDATE platform_fees SET status = 'paid' WHERE status = 'transferred'");
    }

    public function down(): void
    {
        Schema::table('platform_fees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('monthly_invoice_id');
        });
    }
};
```

- [ ] **Step 3: Run migrations**

Run: `php artisan migrate`
Expected: Tables created successfully.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add monthly_invoices table and update platform_fees"
```

---

### Task 2: MonthlyInvoice Model

**Files:**
- Create: `app/Models/MonthlyInvoice.php`

- [ ] **Step 1: Create model**

```bash
php artisan make:model MonthlyInvoice
```

- [ ] **Step 2: Write model with relations and casts**

```php
<?php

namespace App\Models;

use Database\Factories\MonthlyInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'stripe_invoice_id',
        'invoice_number',
        'period',
        'total_fees',
        'stripe_status',
        'paid_at',
        'stripe_invoice_url',
        'stripe_invoice_pdf',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function platformFees(): HasMany
    {
        return $this->hasMany(PlatformFee::class);
    }

    protected function casts(): array
    {
        return [
            'period' => 'date:Y-m-d',
            'total_fees' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 3: Create factory**

```php
<?php

namespace Database\Factories;

use App\Models\MonthlyInvoice;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonthlyInvoiceFactory extends Factory
{
    protected $model = MonthlyInvoice::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'stripe_invoice_id' => 'in_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'period' => now()->startOfMonth()->subMonth(),
            'total_fees' => fake()->randomFloat(2, 10, 1000),
            'stripe_status' => 'open',
            'stripe_invoice_url' => fake()->url(),
        ];
    }
}
```

- [ ] **Step 4: Update PlatformFee model with new relation and enum casts**

```php
<?php

namespace App\Models;

use Database\Factories\PlatformFeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['donation_id', 'organization_id', 'fee_amount', 'fee_percentage', 'stripe_transfer_id', 'status', 'monthly_invoice_id'])]
class PlatformFee extends Model
{
    /** @use HasFactory<PlatformFeeFactory> */
    use HasFactory;

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function monthlyInvoice(): BelongsTo
    {
        return $this->belongsTo(MonthlyInvoice::class);
    }

    protected function casts(): array
    {
        return [
            'fee_amount' => 'decimal:2',
            'fee_percentage' => 'decimal:2',
        ];
    }
}
```

- [ ] **Step 5: Update PlatformFee factory to include monthly_invoice_id**

```php
final class PlatformFeeFactory extends Factory
{
    protected $model = PlatformFee::class;

    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'organization_id' => Organization::factory(),
            'fee_amount' => fake()->randomFloat(2, 0.5, 100),
            'fee_percentage' => 2.5,
            'status' => 'pending',
            'stripe_transfer_id' => null,
        ];
    }

    public function invoiced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'invoiced',
            'monthly_invoice_id' => MonthlyInvoice::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'monthly_invoice_id' => MonthlyInvoice::factory(),
        ]);
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/MonthlyInvoice.php database/factories/MonthlyInvoiceFactory.php app/Models/PlatformFee.php database/factories/PlatformFeeFactory.php
git commit -m "feat: add MonthlyInvoice model, update PlatformFee with invoice relation"
```

---

### Task 3: Remove application_fee_amount from PaymentIntent

**Files:**
- Modify: `app/Actions/Stripe/CreatePaymentIntent.php:62-63`

- [ ] **Step 1: Remove application_fee_amount from PaymentIntent params**

Replace line 62:
```php
$params['application_fee_amount'] = (int) round((float) $donation->gross_amount * $this->platformFeePercent() / 100 * 100);
```
With nothing (remove the line entirely).

Also remove the `platformFeePercent` private method (lines 70-73) since it's now unused from this file.

- [ ] **Step 2: Verify the change**

Read the file to confirm `application_fee_amount` is gone.

- [ ] **Step 3: Commit**

```bash
git add app/Actions/Stripe/CreatePaymentIntent.php
git commit -m "feat: remove application_fee_amount from PaymentIntent"
```

---

### Task 4: Remove application_fee_percent from RecurringSubscription

**Files:**
- Modify: `app/Actions/Stripe/CreateRecurringSubscription.php:135-137`

- [ ] **Step 1: Remove application_fee_percent from subscription params**

Remove the block:
```php
if ($stripeOptions !== []) {
    $params['application_fee_percent'] = $this->platformFeePercent();
}
```

Also remove the `platformFeePercent` private method (lines 144-147) since it's now unused from this file.

- [ ] **Step 2: Verify the change**

Read the file to confirm `application_fee_percent` is gone.

- [ ] **Step 3: Commit**

```bash
git add app/Actions/Stripe/CreateRecurringSubscription.php
git commit -m "feat: remove application_fee_percent from recurring subscriptions"
```

---

### Task 5: Create PlatformFee records on donation success

**Files:**
- Modify: `app/Actions/Stripe/SyncDonationStripeDetails.php:29-36`

- [ ] **Step 1: Update SyncDonationStripeDetails to create PlatformFee record and adjust net_amount**

Replace the `sync` method's donation update block (lines 29-36) with:

```php
$donation->update([
    'stripe_charge_id' => $chargeId,
    'stripe_fee' => $stripeFee,
    'platform_fee' => $platformFee,
    'payment_method_brand' => $cardBrand,
    'payment_method_type' => $paymentMethodType,
    'net_amount' => (float) $donation->gross_amount - $stripeFee,
]);

if ($platformFee > 0 && $donation->organization_id !== null) {
    $donation->platformFee()->create([
        'organization_id' => $donation->organization_id,
        'fee_amount' => $platformFee,
        'fee_percentage' => $this->platformFeePercent(),
        'status' => 'pending',
    ]);
}
```

Note: `$donation->organization_id` doesn't exist directly on donation. The donation belongs to a campaign which belongs to an organization. We need to eager load or access via relation.

Actually, looking at the code flow, by the time `SyncDonationStripeDetails` is called, the donation is already loaded with campaign+organization. Let me verify.

In `DonationForm::confirmPayment()`:
```php
$donation->loadMissing('campaign.organization');
```

In `ProcessStripeWebhook::handleInvoicePaid()`:
- A donation is freshly created with `campaign_id`, so after creation we could load the relation.

But in `SyncDonationStripeDetails`, the `donation` is passed in. We need to ensure the organization is accessible.

Let me add a helper:

```php
private function resolveOrganizationId(Donation $donation): ?int
{
    if ($donation->relationLoaded('campaign') && $donation->campaign->relationLoaded('organization')) {
        return $donation->campaign->organization->id;
    }

    return $donation->campaign->organization_id ?? null;
}
```

And modify the create call to use it.

Wait, `$donation->campaign` is a relation. The `campaign_id` is on the donation. And `campaign.organization_id` is on campaigns table. So we can do:

```php
$organizationId = $donation->campaign->organization_id ?? $donation->campaign()->value('organization_id');
```

Actually, let's just load it eagerly. The cleanest way is to update the code where `SyncDonationStripeDetails` is called to ensure the relation is loaded, OR load it inside the method.

Let me keep it simple - load inside the method:

```php
if ($platformFee > 0) {
    $donation->loadMissing('campaign.organization');
    $organizationId = $donation->campaign?->organization_id;

    if ($organizationId !== null) {
        $donation->platformFee()->create([
            'organization_id' => $organizationId,
            'fee_amount' => $platformFee,
            'fee_percentage' => $this->platformFeePercent(),
            'status' => 'pending',
        ]);
    }
}
```

This should handle both the Livewire flow and the webhook flow.

Also update `net_amount` to not subtract `platform_fee`:
Change line 35:
```php
'net_amount' => (float) $donation->gross_amount - $stripeFee - $platformFee,
```
To:
```php
'net_amount' => (float) $donation->gross_amount - $stripeFee,
```

- [ ] **Step 2: Verify**

Read the updated file to confirm the changes.

- [ ] **Step 3: Commit**

```bash
git add app/Actions/Stripe/SyncDonationStripeDetails.php
git commit -m "feat: create PlatformFee record on donation success, adjust net_amount"
```

---

### Task 6: Monthly Invoice Generation Command

**Files:**
- Create: `app/Console/Commands/GenerateMonthlyInvoices.php`

- [ ] **Step 1: Create command**

```bash
php artisan make:command GenerateMonthlyInvoices
```

- [ ] **Step 2: Write the command logic**

```php
<?php

namespace App\Console\Commands;

use App\Models\MonthlyInvoice;
use App\Models\Organization;
use App\Models\PlatformFee;
use Illuminate\Console\Command;
use Stripe\Invoice as StripeInvoice;
use Stripe\InvoiceItem;
use Stripe\Stripe;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'ihsan:generate-monthly-invoices
        {--period= : The period to invoice for (Y-m-d format, defaults to previous month)}';

    protected $description = 'Generate Stripe Invoices for accumulated platform fees';

    public function handle(): int
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $period = $this->option('period')
            ? carbon($this->option('period'))->startOfMonth()
            : now()->subMonth()->startOfMonth();

        $this->info("Generating invoices for period: {$period->format('Y-m')}");

        $pendingFees = PlatformFee::query()
            ->where('status', 'pending')
            ->where('created_at', '>=', $period->copy()->startOfMonth())
            ->where('created_at', '<', $period->copy()->addMonth()->startOfMonth())
            ->get();

        if ($pendingFees->isEmpty()) {
            $this->info('No pending fees found for this period.');

            return Command::SUCCESS;
        }

        $feesByOrg = $pendingFees->groupBy('organization_id');
        $generated = 0;
        $skipped = 0;

        foreach ($feesByOrg as $organizationId => $fees) {
            $organization = Organization::find($organizationId);

            if ($organization === null || $organization->contact_email === null) {
                $this->warn("Skipping organization #{$organizationId}: no contact email");

                $skipped++;

                continue;
            }

            $totalFees = $fees->sum('fee_amount');

            if ($totalFees <= 0) {
                $skipped++;

                continue;
            }

            try {
                $customerParams = [
                    'email' => $organization->contact_email,
                    'name' => $organization->name,
                    'metadata' => [
                        'organization_id' => (string) $organization->id,
                    ],
                ];

                $customers = \Stripe\Customer::all(['email' => $organization->contact_email, 'limit'  => 1]);
                $customer = $customers->first() ?? \Stripe\Customer::create($customerParams);

                InvoiceItem::create([
                    'customer' => $customer->id,
                    'amount' => (int) ($totalFees * 100),
                    'currency' => 'myr',
                    'description' => "Ihsan Platform Fees — {$period->format('F Y')}",
                ]);

                $stripeInvoice = StripeInvoice::create([
                    'customer' => $customer->id,
                    'collection_method' => 'send_invoice',
                    'days_until_due' => 14,
                    'description' => "Ihsan Platform Fees for {$period->format('F Y')} — {$organization->name}",
                    'metadata' => [
                        'organization_id' => (string) $organization->id,
                        'period' => $period->format('Y-m-d'),
                        'type' => 'platform_fees',
                    ],
                ]);

                $stripeInvoice->finalizeInvoice();

                $invoiceNumber = 'INV-' . $period->format('Ym') . '-' . str_pad((string) ($generated + 1), 3, '0', STR_PAD_LEFT);

                $monthlyInvoice = MonthlyInvoice::create([
                    'organization_id' => $organization->id,
                    'stripe_invoice_id' => $stripeInvoice->id,
                    'invoice_number' => $invoiceNumber,
                    'period' => $period->format('Y-m-d'),
                    'total_fees' => $totalFees,
                    'stripe_status' => $stripeInvoice->status,
                    'stripe_invoice_url' => $stripeInvoice->hosted_invoice_url,
                    'stripe_invoice_pdf' => $stripeInvoice->invoice_pdf,
                ]);

                PlatformFee::whereIn('id', $fees->pluck('id'))->update([
                    'status' => 'invoiced',
                    'monthly_invoice_id' => $monthlyInvoice->id,
                ]);

                $this->info("Invoice {$invoiceNumber} sent to {$organization->name}: MYR {$totalFees}");

                $generated++;
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for {$organization->name}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Generated: {$generated}, Skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/GenerateMonthlyInvoices.php
git commit -m "feat: add ihsan:generate-monthly-invoices command"
```

---

### Task 7: Admin UI — Platform Fees Page

**Files:**
- Create: `app/Filament/Pages/PlatformFees.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

- [ ] **Step 1: Create PlatformFees page**

```php
<?php

namespace App\Filament\Pages;

use App\Models\Organization;
use App\Models\PlatformFee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformFees extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Platform Fees';

    protected static ?int $navigationSort = 18;

    public function table(Table $table): Table
    {
        return $table
            ->query(PlatformFee::query()->with(['donation.donor', 'donation.campaign', 'organization', 'monthlyInvoice']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('NGO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('donation.campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('donation.donor.name')
                    ->label('Donor')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('donation.gross_amount')
                    ->label('Donation')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('fee_amount')
                    ->label('Fee')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('fee_percentage')
                    ->label('Rate')
                    ->formatStateUsing(fn (string $state): string => $state.'%')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'invoiced' => 'info',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('monthlyInvoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label('NGO')
                    ->options(Organization::pluck('name', 'id')->toArray()),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'invoiced' => 'Invoiced',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ]),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('date_from')->label('From'),
                        DatePicker::make('date_to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '>=', $d))
                            ->when($data['date_to'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check')
                    ->visible(fn (PlatformFee $record): bool => $record->status === 'pending')
                    ->action(fn (PlatformFee $record) => $record->update(['status' => 'paid']))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([]);
    }
}
```

- [ ] **Step 2: Register page in AdminPanelProvider**

Add to the `pages()` method in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use App\Filament\Pages\PlatformFees;
use App\Filament\Pages\MonthlyInvoices;

// In the pages() method:
pages([
    Dashboard::class,
    PlatformOverview::class,
    Transactions::class,
    PlatformFees::class,
    Revenue::class,
    MonthlyInvoices::class,
]),
```

- [ ] **Step 3: Commit**

```bash
git add app/Filament/Pages/PlatformFees.php app/Providers/Filament/AdminPanelProvider.php
git commit -m "feat: add Platform Fees admin page"
```

---

### Task 8: Admin UI — Monthly Invoices Page

**Files:**
- Create: `app/Filament/Pages/MonthlyInvoices.php`

- [ ] **Step 1: Create MonthlyInvoices page**

```php
<?php

namespace App\Filament\Pages;

use App\Models\MonthlyInvoice;
use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class MonthlyInvoices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Monthly Invoices';

    protected static ?int $navigationSort = 19;

    public string $totalOutstanding = '0.00';

    public string $totalCollected = '0.00';

    public int $invoicesSent = 0;

    public function mount(): void
    {
        $this->totalOutstanding = number_format((float) MonthlyInvoice::query()
            ->whereIn('stripe_status', ['open', 'uncollectible'])
            ->sum('total_fees'), 2, '.', '');

        $this->totalCollected = number_format((float) MonthlyInvoice::query()
            ->where('stripe_status', 'paid')
            ->sum('total_fees'), 2, '.', '');

        $this->invoicesSent = MonthlyInvoice::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(MonthlyInvoice::query()->with('organization'))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('NGO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period')
                    ->label('Period')
                    ->date('F Y')
                    ->sortable(),
                TextColumn::make('total_fees')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('stripe_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'open' => 'warning',
                        'uncollectible' => 'danger',
                        'void' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString()),
                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('stripe_invoice_id')
                    ->label('Stripe ID')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label('NGO')
                    ->options(Organization::pluck('name', 'id')->toArray()),
                SelectFilter::make('stripe_status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'paid' => 'Paid',
                        'uncollectible' => 'Uncollectible',
                        'void' => 'Void',
                    ]),
                Filter::make('period')
                    ->form([
                        DatePicker::make('period_from')->label('From'),
                        DatePicker::make('period_to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['period_from'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('period', '>=', $d))
                            ->when($data['period_to'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('period', '<=', $d));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('view_stripe')
                    ->label('View in Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MonthlyInvoice $record): ?string => $record->stripe_invoice_url)
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-sparkles')
                    ->action(function () {
                        $exitCode = Artisan::call('ihsan:generate-monthly-invoices');

                        $message = $exitCode === 0
                            ? 'Invoices generated successfully.'
                            : 'Invoice generation failed. Check logs.';

                        Notification::make()
                            ->title($message)
                            ->success($exitCode === 0)
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generate Monthly Invoices')
                    ->modalDescription('This will create Stripe Invoices for all pending platform fees from the previous month. Continue?'),
            ]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Filament/Pages/MonthlyInvoices.php
git commit -m "feat: add Monthly Invoices admin page with stats and generate action"
```

---

### Task 9: Webhook Handling — Invoice Paid for Platform Invoices

**Files:**
- Modify: `app/Jobs/ProcessStripeWebhook.php`

- [ ] **Step 1: Add handler for invoice.paid (platform invoices)**

Add to the `match` statement in the `handle()` method:
```php
'invoice.paid' => $this->handlePlatformInvoicePaid($event),
```

But wait — `invoice.paid` is already handled for donor subscriptions. We need to differentiate between donor subscription invoices and platform fee invoices. The existing `handleInvoicePaid` handles donor subscription invoices (where the invoice belongs to a connected account). Platform invoices are created on the platform's own Stripe account.

A simple way to differentiate: check metadata. If the invoice metadata has `type = 'platform_fees'`, it's a platform invoice.

So the match should be:
```php
match ($event->type) {
    // ... existing cases ...
    'invoice.paid' => $this->handleDonorInvoicePaid($event), // rename from handleInvoicePaid
    // ... existing cases ...
    default => null,
};
```

Actually, looking at the existing code more carefully, the `invoice.paid` and `invoice.payment_failed` events come from the platform's own Stripe account (not connected accounts). The existing `handleInvoicePaid` processes these for donor subscriptions by looking up the subscription.

The platform invoices would also come via the same webhook endpoint since they're on the platform account. So we need to differentiate.

Method: Check if the invoice has metadata `type = 'platform_fees'`. If yes, handle it as a platform invoice payment. If no, fall through to the existing donor subscription handling.

Let me create a renamed handler or a combined one.

Actually, the cleanest approach: rename existing `handleInvoicePaid` to `handleDonorInvoicePaid` and add a new `handlePlatformInvoicePaid`. In the match:

```php
'invoice.paid' => function () use ($event) {
    $invoice = $event->data->object;
    $metadata = $invoice->metadata ?? [];
    
    if (($metadata['type'] ?? null) === 'platform_fees') {
        $this->handlePlatformInvoicePaid($event);
    } else {
        $this->handleDonorInvoicePaid($event);
    }
},
```

Add the new handler method:

```php
private function handlePlatformInvoicePaid(StripeEvent $event): void
{
    $invoice = $event->data->object;
    $metadata = $invoice->metadata ?? [];

    $organizationId = $metadata['organization_id'] ?? null;

    if ($organizationId === null) {
        return;
    }

    $monthlyInvoice = MonthlyInvoice::query()
        ->where('stripe_invoice_id', $invoice->id)
        ->first();

    if ($monthlyInvoice === null) {
        return;
    }

    $monthlyInvoice->update([
        'stripe_status' => $invoice->status,
        'paid_at' => now(),
    ]);

    PlatformFee::query()
        ->where('monthly_invoice_id', $monthlyInvoice->id)
        ->update(['status' => 'paid']);
}
```

And rename `handleInvoicePaid` to `handleDonorInvoicePaid` (update the method name and the `match` reference).

Also add `use App\Models\MonthlyInvoice;` and `use App\Models\PlatformFee;` to the imports.

- [ ] **Step 2: Implement the changes**

Update the `handle()` method's `match` for `invoice.paid` and add the new method.

- [ ] **Step 3: Commit**

```bash
git add app/Jobs/ProcessStripeWebhook.php
git commit -m "feat: handle invoice.paid webhook for platform fee invoices"
```

---

### Task 10: Schedule the monthly invoice command

**Files:**
- Modify: `routes/console.php`

- [ ] **Step 1: Add the schedule**

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('ihsan:generate-monthly-invoices')->monthlyOn(1, '08:00');
```

- [ ] **Step 2: Commit**

```bash
git add routes/console.php
git commit -m "feat: schedule monthly invoice generation on 1st of month"
```

---

### Task 11: Run Pint

- [ ] **Step 1: Format all modified files**

```bash
vendor/bin/pint --format agent
```

- [ ] **Step 2: Commit**

```bash
git add -A
git commit -m "style: apply pint formatting"
```

---

### Task 12: Write Tests

**Files:**
- Create: `tests/Feature/Ihsan/PlatformFeesPageTest.php`
- Create: `tests/Feature/Ihsan/MonthlyInvoicesPageTest.php`
- Create: `tests/Feature/Ihsan/GenerateMonthlyInvoicesCommandTest.php`
- Modify: `tests/Feature/Ihsan/RevenuePageTest.php` (update for new net_amount calc)
- Modify: `tests/Feature/Ihsan/PlatformOverviewTest.php` (update for new status)

- [ ] **Step 1: Write PlatformFeesPageTest**

```php
<?php

use App\Filament\Pages\PlatformFees;
use App\Models\Organization;
use App\Models\PlatformFee;
use App\Models\User;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Campaign;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    actingAs($this->admin);
});

it('can render the page', function () {
    livewire(PlatformFees::class)
        ->assertSuccessful();
});

it('displays platform fees', function () {
    $fee = PlatformFee::factory()->create([
        'fee_amount' => 25.00,
        'fee_percentage' => 2.5,
        'status' => 'pending',
    ]);

    livewire(PlatformFees::class)
        ->assertSee('MYR 25.00')
        ->assertSee('Pending');
});

it('can filter by status', function () {
    PlatformFee::factory()->create(['status' => 'pending', 'fee_amount' => 10]);
    PlatformFee::factory()->create(['status' => 'paid', 'fee_amount' => 20]);

    livewire(PlatformFees::class)
        ->assertCanSeeTableRecords(PlatformFee::where('status', 'pending')->get())
        ->filterTable('status', 'pending')
        ->assertCountTableRecords(1);
});

it('can mark fee as paid', function () {
    $fee = PlatformFee::factory()->create(['status' => 'pending']);

    livewire(PlatformFees::class)
        ->callTableAction('mark_paid', $fee);

    expect($fee->fresh()->status)->toBe('paid');
});
```

- [ ] **Step 2: Write MonthlyInvoicesPageTest**

```php
<?php

use App\Filament\Pages\MonthlyInvoices;
use App\Models\MonthlyInvoice;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    actingAs($this->admin);
});

it('can render the page', function () {
    livewire(MonthlyInvoices::class)
        ->assertSuccessful();
});

it('displays monthly invoices', function () {
    $invoice = MonthlyInvoice::factory()->create([
        'total_fees' => 150.00,
        'stripe_status' => 'open',
    ]);

    livewire(MonthlyInvoices::class)
        ->assertSee($invoice->invoice_number)
        ->assertSee('MYR 150.00');
});

it('shows stats cards', function () {
    MonthlyInvoice::factory()->create(['total_fees' => 100, 'stripe_status' => 'open']);
    MonthlyInvoice::factory()->create(['total_fees' => 200, 'stripe_status' => 'paid']);

    livewire(MonthlyInvoices::class)
        ->assertSet('totalOutstanding', '100.00')
        ->assertSet('totalCollected', '200.00');
});
```

- [ ] **Step 3: Write GenerateMonthlyInvoicesCommandTest**

This requires a Stripe API key, so make it a unit test that doesn't hit Stripe, or skip it if no Stripe key. For now, test the basic command structure:

```php
<?php

use App\Console\Commands\GenerateMonthlyInvoices;
use App\Models\Organization;
use App\Models\PlatformFee;

it('has the correct signature', function () {
    $command = new GenerateMonthlyInvoices();
    expect($command->getName())->toBe('ihsan:generate-monthly-invoices');
});

it('reports success when no pending fees', function () {
    Artisan::call('ihsan:generate-monthly-invoices');

    Artisan::output();
})->todo('Needs Stripe API keys for full integration test');
```

- [ ] **Step 4: Update existing tests for net_amount change**

Run: `php artisan test --compact --filter=RevenuePageTest`
Check if any tests fail due to the `net_amount` change. If so, update them.

Run: `php artisan test --compact --filter=PlatformOverviewTest`
Check if any tests fail. If so, update them.

- [ ] **Step 5: Run the test suite**

Run: `php artisan test --compact`
Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add tests/
git commit -m "test: add tests for platform fees, invoices, and payment flow changes"
```

---

### Verification

- [ ] **Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: All tests pass.

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --format agent`
Expected: No changes needed (or minor formatting).

- [ ] **Step 3: Final commit**

```bash
git add -A
git commit -m "chore: final cleanup and formatting"
```
