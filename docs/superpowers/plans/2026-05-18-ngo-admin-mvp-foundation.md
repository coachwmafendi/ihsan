# NGO Admin MVP Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first working Ihsan NGO Admin foundation: data model, tenant roles, FilamentPHP v5 app/admin panels, and an Insights page that can read real donation/subscription data.

**Architecture:** Use FilamentPHP v5 as the primary admin surface. Keep two panels: `/app` for NGO admins and `/admin` for platform super admins. Use shared database multi-tenancy through `organization_id`, with global donors scoped to NGO access through campaigns and subscriptions.

**Tech Stack:** Laravel 13.9, PHP 8.5 runtime, FilamentPHP 5.6, Livewire 4, Flux UI 2, Pest 4, SQLite local database.

---

## Product Scope For This Plan

This plan implements the foundation only. It does not integrate Stripe checkout, real webhooks, email receipts, or the public donation widget yet. Those depend on stable models, panel access, and admin UX from this foundation.

Fundraise Up is the UX benchmark. For MVP, include these NGO app areas:

- Insights
- Donations
- Recurring
- Campaigns
- Supporters
- Elements
- Exports
- Settings

Keep these out of MVP foundation:

- Designations
- Virtual Terminal
- Fundraisers
- Benefits
- Gift catalogs
- Tributes

## File Structure

Create or modify these files:

- Modify: `database/migrations/0001_01_01_000000_create_users_table.php` to add `organization_id` and `role`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_ihsan_foundation_tables.php`
- Create: `app/Enums/UserRole.php`
- Create: `app/Enums/OrganizationStatus.php`
- Create: `app/Enums/CampaignStatus.php`
- Create: `app/Enums/DonationStatus.php`
- Create: `app/Enums/DonationType.php`
- Create: `app/Enums/SubscriptionStatus.php`
- Create: `app/Enums/SubscriptionInterval.php`
- Create: `app/Enums/ElementType.php`
- Create: `app/Models/Organization.php`
- Create: `app/Models/OrganizationDocument.php`
- Create: `app/Models/Campaign.php`
- Create: `app/Models/Donor.php`
- Create: `app/Models/Donation.php`
- Create: `app/Models/Subscription.php`
- Create: `app/Models/Element.php`
- Create: `app/Models/PlatformFee.php`
- Create: `app/Models/WebhookLog.php`
- Modify: `app/Models/User.php`
- Create: factories for every new Eloquent model in `database/factories`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `app/Providers/Filament/AppPanelProvider.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `app/Filament/App/Pages/Insights.php`
- Create: `app/Filament/App/Resources/CampaignResource.php`
- Create: `app/Filament/App/Resources/ElementResource.php`
- Create: `app/Filament/App/Resources/DonationResource.php`
- Create: `app/Filament/App/Resources/SubscriptionResource.php`
- Create: `app/Filament/App/Resources/DonorResource.php`
- Create: `app/Filament/App/Resources/ExportResource.php` only if Filament resources are preferred for exports; otherwise create a custom page `app/Filament/App/Pages/Exports.php`
- Create: `app/Filament/Resources/OrganizationResource.php` for super admin approval
- Create: `tests/Feature/Ihsan/FoundationSchemaTest.php`
- Create: `tests/Feature/Ihsan/TenantAccessTest.php`
- Create: `tests/Feature/Ihsan/AppPanelNavigationTest.php`
- Create: `tests/Feature/Ihsan/InsightsPageTest.php`

Use Laravel and Filament generators where possible:

```bash
php artisan make:model Organization --factory --no-interaction
php artisan make:model Campaign --factory --no-interaction
php artisan make:filament-resource Campaign --panel=app --generate --no-interaction
php artisan make:filament-page Insights --panel=app --no-interaction
php artisan make:test --pest Ihsan/FoundationSchemaTest --no-interaction
```

The exact generator options may differ by Filament 5 command signature. Check with `php artisan make:filament-resource --help` before running each Filament generator.

---

## Task 1: Schema And Enum Foundation

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_ihsan_foundation_tables.php`
- Create: enum files under `app/Enums`
- Test: `tests/Feature/Ihsan/FoundationSchemaTest.php`

- [ ] **Step 1: Search version-specific docs**

Run:

```bash
php artisan make:model --help
php artisan make:migration --help
```

Expected: commands show available Laravel 13 generator options.

- [ ] **Step 2: Write failing schema test**

Create `tests/Feature/Ihsan/FoundationSchemaTest.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;

it('has the ihsan foundation tables', function () {
    foreach ([
        'organizations',
        'organization_documents',
        'campaigns',
        'donors',
        'donations',
        'subscriptions',
        'elements',
        'platform_fees',
        'webhook_logs',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table [{$table}]");
    }
});

it('adds tenant and role columns to users', function () {
    expect(Schema::hasColumns('users', [
        'organization_id',
        'role',
    ]))->toBeTrue();
});

it('has key columns for organization-scoped fundraising', function () {
    expect(Schema::hasColumns('campaigns', [
        'organization_id',
        'title',
        'slug',
        'target_amount',
        'collected_amount',
        'allow_recurring',
        'status',
        'suggested_amounts',
    ]))->toBeTrue();

    expect(Schema::hasColumns('donations', [
        'campaign_id',
        'donor_id',
        'subscription_id',
        'gross_amount',
        'platform_fee',
        'net_amount',
        'status',
        'type',
        'utm_params',
    ]))->toBeTrue();
});
```

- [ ] **Step 3: Run failing test**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/FoundationSchemaTest.php
```

Expected: FAIL because the foundation tables and user columns do not exist yet.

- [ ] **Step 4: Create enums**

Create these enum classes:

```php
<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case NgoAdmin = 'ngo_admin';
}
```

Use the same pattern for:

```php
enum OrganizationStatus: string { case Pending = 'pending'; case Active = 'active'; case Suspended = 'suspended'; case Rejected = 'rejected'; }
enum CampaignStatus: string { case Draft = 'draft'; case Active = 'active'; case Paused = 'paused'; case Ended = 'ended'; }
enum DonationStatus: string { case Pending = 'pending'; case Succeeded = 'succeeded'; case Failed = 'failed'; case Refunded = 'refunded'; }
enum DonationType: string { case OneTime = 'one_time'; case Recurring = 'recurring'; }
enum SubscriptionStatus: string { case Active = 'active'; case Paused = 'paused'; case Cancelled = 'cancelled'; case PastDue = 'past_due'; case Incomplete = 'incomplete'; }
enum SubscriptionInterval: string { case Weekly = 'weekly'; case Monthly = 'monthly'; case Yearly = 'yearly'; }
enum ElementType: string { case Button = 'button'; case Form = 'form'; case Popup = 'popup'; }
```

- [ ] **Step 5: Update users migration**

In `database/migrations/0001_01_01_000000_create_users_table.php`, add after `$table->id();`:

```php
$table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
```

Add after `$table->string('email')->unique();`:

```php
$table->string('role')->default('ngo_admin')->index();
```

Important: because `organizations` is created after `users`, create `organization_id` as a nullable unsigned bigint in the users migration, then add the foreign key in the foundation migration after `organizations` exists if SQLite complains about migration order:

```php
$table->foreignId('organization_id')->nullable()->index();
```

- [ ] **Step 6: Create foundation migration**

Use `php artisan make:migration create_ihsan_foundation_tables --no-interaction`, then implement:

```php
Schema::create('organizations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('ros_rob_number')->nullable()->unique();
    $table->string('registration_type')->default('others');
    $table->text('description')->nullable();
    $table->string('logo_path')->nullable();
    $table->string('website_url')->nullable();
    $table->string('contact_email')->nullable();
    $table->string('contact_phone')->nullable();
    $table->string('status')->default('pending')->index();
    $table->string('stripe_account_id')->nullable()->unique();
    $table->boolean('stripe_onboarded')->default(false);
    $table->string('bank_account_name')->nullable();
    $table->string('bank_account_number')->nullable();
    $table->string('bank_name')->nullable();
    $table->json('settings')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

Then create the remaining tables following `ERD.md` exactly. Use `decimal(..., 12, 2)` for MYR amounts. Add indexes for status, type, created date, Stripe ids, and tenant join columns listed in `ERD.md`.

- [ ] **Step 7: Run schema test**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/FoundationSchemaTest.php
```

Expected: PASS.

- [ ] **Step 8: Format PHP**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

Expected: Pint reports formatted files or no changes.

---

## Task 2: Models, Relationships, Factories, And Seed Data

**Files:**
- Create/modify Eloquent models under `app/Models`
- Create factories under `database/factories`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Ihsan/TenantAccessTest.php`

- [ ] **Step 1: Write failing relationship test**

Create `tests/Feature/Ihsan/TenantAccessTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;

it('connects users to their organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    expect($user->organization)->toBeInstanceOf(Organization::class);
    expect($organization->users)->toHaveCount(1);
});

it('scopes donors through organization campaigns', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $campaign = Campaign::factory()->for($organization)->create();
    $otherCampaign = Campaign::factory()->for($otherOrganization)->create();

    $donor = Donor::factory()->create(['email' => 'same@example.test']);

    Donation::factory()->for($campaign)->for($donor)->create();
    Donation::factory()->for($otherCampaign)->for($donor)->create();
    Subscription::factory()->for($campaign)->for($donor)->create();

    expect($organization->campaigns)->toHaveCount(1);
    expect($campaign->donations)->toHaveCount(1);
    expect($campaign->subscriptions)->toHaveCount(1);
});
```

- [ ] **Step 2: Run failing relationship test**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/TenantAccessTest.php
```

Expected: FAIL because models/factories/relationships do not exist.

- [ ] **Step 3: Implement User model changes**

Modify `app/Models/User.php`:

```php
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'name', 'email', 'password', 'role'])]
class User extends Authenticatable
{
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
```

Keep existing methods such as `initials()`.

- [ ] **Step 4: Implement model relationship pattern**

Use this pattern for `Organization`:

```php
<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'ros_rob_number', 'registration_type', 'description', 'logo_path', 'website_url', 'contact_email', 'contact_phone', 'status', 'stripe_account_id', 'stripe_onboarded', 'bank_account_name', 'bank_account_number', 'bank_name', 'settings', 'approved_at', 'approved_by'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'stripe_onboarded' => 'boolean',
            'approved_at' => 'datetime',
            'status' => OrganizationStatus::class,
        ];
    }
}
```

Implement equivalent relationships:

- `Campaign belongsTo Organization`, `hasMany Donation`, `hasMany Subscription`, `hasMany Element`
- `Donor hasMany Donation`, `hasMany Subscription`
- `Donation belongsTo Campaign`, `belongsTo Donor`, `belongsTo Subscription`, `hasOne PlatformFee`
- `Subscription belongsTo Campaign`, `belongsTo Donor`, `hasMany Donation`
- `Element belongsTo Organization`, `belongsTo Campaign`
- `PlatformFee belongsTo Donation`, `belongsTo Organization`
- `WebhookLog` casts `payload` to array and `processed_at` to datetime

- [ ] **Step 5: Implement factories**

Create factories with valid defaults. Example for `OrganizationFactory`:

```php
public function definition(): array
{
    return [
        'name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'registration_type' => 'others',
        'description' => fake()->paragraph(),
        'contact_email' => fake()->safeEmail(),
        'status' => 'active',
        'stripe_onboarded' => false,
        'settings' => [
            'primary_color' => '#0f766e',
            'suggested_amounts' => [30, 50, 100],
        ],
    ];
}
```

For money factories, use fixed amounts such as `gross_amount: 10000`, `platform_fee: 300`, `net_amount: 9700` only if storing cents. If using decimal MYR, use `gross_amount: 100.00`, `platform_fee: 3.00`, `net_amount: 97.00`. Pick one convention and use it consistently.

- [ ] **Step 6: Seed one demo organization**

Modify `database/seeders/DatabaseSeeder.php` to create:

- Super admin: `admin@ihsan.test`
- NGO admin: `ngo@ihsan.test`
- Organization: `Maahad Tahfiz Mumtazatut Taqwa`
- Two campaigns
- Three donors
- Succeeded donations across the last 30 days
- One active monthly subscription

Use `Hash::make('password')` or rely on `User` password cast.

- [ ] **Step 7: Run model tests**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/TenantAccessTest.php
```

Expected: PASS.

- [ ] **Step 8: Run schema plus relationship tests**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan
```

Expected: PASS.

---

## Task 3: Filament Panel Access And Navigation

**Files:**
- Modify: `app/Providers/Filament/AppPanelProvider.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Ihsan/AppPanelNavigationTest.php`

- [ ] **Step 1: Write failing panel access tests**

Create `tests/Feature/Ihsan/AppPanelNavigationTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;

it('allows ngo admins into the app panel', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful();
});

it('allows super admins into the admin panel', function () {
    $user = User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertSuccessful();
});
```

- [ ] **Step 2: Run failing panel access tests**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/AppPanelNavigationTest.php
```

Expected: FAIL if panel access is not role-aware.

- [ ] **Step 3: Implement Filament access contract**

Modify `app/Models/User.php` to implement Filament access:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role === UserRole::SuperAdmin,
            'app' => $this->role === UserRole::NgoAdmin && $this->organization_id !== null,
            default => false,
        };
    }
}
```

- [ ] **Step 4: Configure app panel navigation**

In `app/Providers/Filament/AppPanelProvider.php`, keep `id('app')` and `path('app')`. Set primary color to teal:

```php
->colors([
    'primary' => Color::Teal,
])
```

Do not add Stripe, webhook, or checkout logic in this task.

- [ ] **Step 5: Configure admin panel navigation**

In `app/Providers/Filament/AdminPanelProvider.php`, keep `id('admin')` and `path('admin')`. Set a distinct neutral/amber primary only if desired:

```php
->colors([
    'primary' => Color::Amber,
])
```

- [ ] **Step 6: Run panel access tests**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/AppPanelNavigationTest.php
```

Expected: PASS.

---

## Task 4: Insights Page MVP

**Files:**
- Create: `app/Filament/App/Pages/Insights.php`
- Create: `resources/views/filament/app/pages/insights.blade.php` if custom view is needed
- Test: `tests/Feature/Ihsan/InsightsPageTest.php`

- [ ] **Step 1: Write failing Insights test**

Create `tests/Feature/Ihsan/InsightsPageTest.php`:

```php
<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\App\Pages\Insights;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use function Pest\Livewire\livewire;

it('calculates ngo insights from organization-scoped records', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);
    $donor = Donor::factory()->create();

    $campaign = Campaign::factory()->for($organization)->create();
    $otherCampaign = Campaign::factory()->for($otherOrganization)->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Donation::factory()->for($otherCampaign)->for($donor)->create([
        'gross_amount' => 999.00,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 30.00,
        'status' => SubscriptionStatus::Active,
        'interval' => 'monthly',
    ]);

    $this->actingAs($user);

    livewire(Insights::class)
        ->assertOk()
        ->assertSet('totalRaised', '100.00')
        ->assertSet('monthlyRecurringRevenue', '30.00');
});
```

- [ ] **Step 2: Run failing Insights test**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/InsightsPageTest.php
```

Expected: FAIL because `Insights` page does not exist.

- [ ] **Step 3: Create Filament page**

Run:

```bash
php artisan make:filament-page Insights --panel=app --no-interaction
```

Expected: creates `app/Filament/App/Pages/Insights.php`.

- [ ] **Step 4: Implement scoped calculations**

In `app/Filament/App/Pages/Insights.php`, implement public properties:

```php
public string $totalRaised = '0.00';
public string $monthlyRecurringRevenue = '0.00';
public int $activeRecurringDonors = 0;

public function mount(): void
{
    $organizationId = auth()->user()->organization_id;

    $campaignIds = Campaign::query()
        ->where('organization_id', $organizationId)
        ->pluck('id');

    $this->totalRaised = number_format((float) Donation::query()
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', DonationStatus::Succeeded)
        ->sum('gross_amount'), 2, '.', '');

    $this->monthlyRecurringRevenue = number_format((float) Subscription::query()
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', SubscriptionStatus::Active)
        ->where('interval', 'monthly')
        ->sum('amount'), 2, '.', '');

    $this->activeRecurringDonors = Subscription::query()
        ->whereIn('campaign_id', $campaignIds)
        ->where('status', SubscriptionStatus::Active)
        ->distinct('donor_id')
        ->count('donor_id');
}
```

Add navigation:

```php
protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
protected static ?string $navigationLabel = 'Insights';
protected static ?int $navigationSort = 10;
```

If Filament 5 uses a different type signature for page navigation in the generated class, keep the generated signature and only change values.

- [ ] **Step 5: Add simple page view**

Use Filament stats/widgets if generated page supports it. Otherwise create a Blade view with three cards:

```blade
<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3">
        <x-filament::section>
            <div class="text-sm text-gray-500">Total raised</div>
            <div class="text-2xl font-semibold">MYR {{ $totalRaised }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500">Monthly recurring revenue</div>
            <div class="text-2xl font-semibold">MYR {{ $monthlyRecurringRevenue }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500">Active recurring donors</div>
            <div class="text-2xl font-semibold">{{ $activeRecurringDonors }}</div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
```

- [ ] **Step 6: Run Insights test**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/InsightsPageTest.php
```

Expected: PASS.

---

## Task 5: MVP Filament Resources

**Files:**
- Create: Filament App resources for Campaigns, Elements, Donations, Subscriptions, Donors
- Create: Filament Admin resource for Organizations
- Test: extend `tests/Feature/Ihsan/AppPanelNavigationTest.php`

- [ ] **Step 1: Generate resources**

Run:

```bash
php artisan make:filament-resource Campaign --panel=app --generate --no-interaction
php artisan make:filament-resource Element --panel=app --generate --no-interaction
php artisan make:filament-resource Donation --panel=app --generate --no-interaction
php artisan make:filament-resource Subscription --panel=app --generate --no-interaction
php artisan make:filament-resource Donor --panel=app --generate --no-interaction
php artisan make:filament-resource Organization --panel=admin --generate --no-interaction
```

Expected: Filament creates resource classes and list/create/edit pages.

- [ ] **Step 2: Restrict app resource queries**

For every resource in `app/Filament/App/Resources`, override the Eloquent query to organization scope. Example for `CampaignResource`:

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('organization_id', auth()->user()->organization_id);
}
```

For `DonationResource`, scope through campaign:

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->whereHas('campaign', fn (Builder $query) => $query
            ->where('organization_id', auth()->user()->organization_id));
}
```

Use the same `whereHas('campaign')` pattern for `SubscriptionResource` and `DonorResource`.

- [ ] **Step 3: Set navigation labels and sort order**

Use these labels:

- `CampaignResource`: Campaigns, sort `40`
- `ElementResource`: Elements, sort `60`
- `DonationResource`: Donations, sort `20`
- `SubscriptionResource`: Recurring, sort `30`
- `DonorResource`: Supporters, sort `50`
- `OrganizationResource`: Organizations, sort `10` in admin panel

- [ ] **Step 4: Make Donations read-only in MVP**

In `DonationResource`, disable create/edit/delete actions for NGO admins. Donations should be created by checkout/webhooks later, not manually in this foundation.

- [ ] **Step 5: Make Subscriptions mostly read-only in MVP foundation**

Allow list/view/edit status only if the edit form is limited to `status` and `paused_until`. Do not implement Stripe cancellation calls in this task.

- [ ] **Step 6: Add resource access test**

Extend `tests/Feature/Ihsan/AppPanelNavigationTest.php`:

```php
it('shows app panel resource pages to ngo admins', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $this->actingAs($user);

    foreach ([
        '/app/campaigns',
        '/app/elements',
        '/app/donations',
        '/app/subscriptions',
        '/app/donors',
    ] as $path) {
        $this->get($path)->assertSuccessful();
    }
});
```

If generated route slugs differ, inspect `php artisan route:list --path=app --except-vendor` and update the test to match actual Filament routes.

- [ ] **Step 7: Run app panel tests**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan/AppPanelNavigationTest.php
```

Expected: PASS.

---

## Task 6: Verification And Formatting

**Files:**
- All files touched above

- [ ] **Step 1: Run targeted Ihsan tests**

Run:

```bash
php artisan test --compact tests/Feature/Ihsan
```

Expected: PASS.

- [ ] **Step 2: Run existing auth/dashboard tests**

Run:

```bash
php artisan test --compact tests/Feature/DashboardTest.php tests/Feature/Auth
```

Expected: PASS.

- [ ] **Step 3: Run Pint**

Run:

```bash
vendor/bin/pint --dirty --format agent
```

Expected: no style violations remain.

- [ ] **Step 4: Verify routes**

Run:

```bash
php artisan route:list --path=app --except-vendor
php artisan route:list --path=admin --except-vendor
```

Expected:

- `/app` exists for NGO admin panel
- `/admin` exists for super admin panel
- App panel includes Insights and resource routes
- Admin panel includes Organization resource routes

- [ ] **Step 5: Browser smoke check through Herd**

Use Laravel Boost to get the absolute URL:

```php
get_absolute_url('/app')
```

Then open the URL in Browser and confirm:

- NGO admin can access `/app`
- Super admin can access `/admin`
- Wrong role is denied from the other panel
- Sidebar labels match MVP menu

---

## Self-Review

Spec coverage:

- NGO Admin app first: covered by `/app` Filament panel, Insights, and MVP resources.
- Fundraise Up benchmark: covered by Insights, Donations, Recurring, Campaigns, Supporters, Elements, Exports/CSV placeholder, Settings through panel profile/settings.
- FilamentPHP v5: covered by existing package `filament/filament` 5.6.3, panel provider usage, resources, pages, and Livewire-style tests.
- ERD foundation: covered by schema task, models, factories, relationships, and scoped queries.
- Donor Portal Lite: not in this foundation plan. It belongs after checkout/subscription creation exists.
- Stripe checkout/webhooks: not in this foundation plan. It belongs in the next implementation plan.

Placeholder scan:

- No `TBD` or `TODO` entries are intentionally left.
- Where generated filenames include timestamps, use the timestamp produced by `php artisan make:migration`.
- Where route slugs may differ after Filament generation, the plan includes an exact command to inspect and then update tests to actual routes.

Type consistency:

- Status/type fields use PHP backed enums cast by Eloquent.
- Monetary fields use decimal MYR in examples. Keep decimal MYR throughout this plan.
- Tenant scoping always goes through `organization_id` on campaigns or direct organization ownership.

## Execution Options

Plan complete and saved to `docs/superpowers/plans/2026-05-18-ngo-admin-mvp-foundation.md`.

Two execution options:

1. **Subagent-Driven (recommended)** - dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** - execute tasks in this session using executing-plans, batch execution with checkpoints.

