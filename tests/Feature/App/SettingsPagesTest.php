<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Settings\Account;
use App\Livewire\App\Settings\AllowDomains;
use App\Livewire\App\Settings\DonorPortal;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

// Settings Organization
it('renders settings organization page', function () {
    actingAs($this->user)
        ->get('/app/settings/organization')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Organization');
});

it('redirects guests from settings organization', function () {
    get('/app/settings/organization')->assertRedirect('/login');
});

// Settings Payment
it('renders settings payment page', function () {
    actingAs($this->user)
        ->get('/app/settings/payment')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Payment');
});

// Settings Notifications
it('renders settings notifications page', function () {
    actingAs($this->user)
        ->get('/app/settings/notifications')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Notifications');
});

// Billing
it('renders billing page', function () {
    actingAs($this->user)
        ->get('/app/billing')
        ->assertOk()
        ->assertSee('Billing');
});

// Stripe Onboarding
it('renders stripe onboarding page', function () {
    $organization = Organization::factory()->withoutStripe()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    actingAs($user)
        ->get('/app/stripe-onboarding')
        ->assertOk()
        ->assertSee('Stripe');
});

// Settings Allowed Domains
it('renders settings allowed domains page', function () {
    actingAs($this->user)
        ->get('/app/settings/allow-domains')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Allowed Domains');
});

it('saves allowed domains', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => []],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    Livewire::test(AllowDomains::class)
        ->call('addDomain', 'mywebsite.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh()->settings['allowed_domains'])->toContain('mywebsite.com');
});

it('removes a domain from allowed domains', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['mywebsite.com', 'other.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    Livewire::test(AllowDomains::class)
        ->call('removeDomain', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh()->settings['allowed_domains'])
        ->not->toContain('mywebsite.com')
        ->toContain('other.org');
});

it('normalizes domains on save (strips www, lowercases)', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => []],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    Livewire::test(AllowDomains::class)
        ->call('addDomain', 'https://www.MyWebsite.com/page')
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh()->settings['allowed_domains'])->toBe(['mywebsite.com']);
});

it('enforces a maximum of 10 allowed domains', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => []],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    $component = Livewire::test(AllowDomains::class);

    foreach (range(1, 10) as $i) {
        $component->call('addDomain', "domain-{$i}.com");
    }

    $component->call('save')->assertHasNoErrors();

    expect($organization->fresh()->settings['allowed_domains'])->toHaveCount(10);

    $component->call('addDomain', 'domain-11.com');

    $component->call('save')->assertHasNoErrors();

    expect($organization->fresh()->settings['allowed_domains'])->toHaveCount(10);
});

// Settings Donor Portal
it('renders settings donor portal page', function () {
    actingAs($this->user)
        ->get('/app/settings/donor-portal')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Donor Portal');
});

it('saves donor portal settings', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'contact_email' => 'org@example.com',
        'settings' => [],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    Livewire::test(DonorPortal::class)
        ->set('portal_tagline', 'Support our cause')
        ->set('portal_reply_to_email', 'replies@example.com')
        ->set('portal_receipt_footer', 'Thank you for your generosity.')
        ->call('save')
        ->assertHasNoErrors();

    $settings = $organization->fresh()->settings;

    expect($settings['portal_tagline'])->toBe('Support our cause');
    expect($settings['portal_reply_to_email'])->toBe('replies@example.com');
    expect($settings['portal_receipt_footer'])->toBe('Thank you for your generosity.');
});

it('defaults donor portal reply-to email to organization contact email', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'contact_email' => 'contact@example.com',
        'settings' => [],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    Livewire::test(DonorPortal::class)
        ->assertSet('portal_reply_to_email', 'contact@example.com');
});

// Settings Account
it('renders settings account page', function () {
    actingAs($this->user)
        ->get('/app/settings/account')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Account');
});

it('redirects guests from settings account', function () {
    get('/app/settings/account')->assertRedirect('/login');
});

it('updates account name', function () {
    actingAs($this->user);

    Livewire::test(Account::class)
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->user->fresh()->name)->toBe('Updated Name');
});

it('updates password with correct current password', function () {
    actingAs($this->user);

    Livewire::test(Account::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('save')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $this->user->fresh()->password))->toBeTrue();
});

it('rejects password update with incorrect current password', function () {
    actingAs($this->user);

    Livewire::test(Account::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('save')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

it('rejects duplicate email', function () {
    $otherUser = User::factory()->create([
        'organization_id' => $this->organization->id,
        'email' => 'taken@example.com',
    ]);

    actingAs($this->user);

    Livewire::test(Account::class)
        ->set('name', 'Name')
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

it('prevents organization admin from changing email', function () {
    actingAs($this->user);

    Livewire::test(Account::class)
        ->set('name', 'Updated Name')
        ->set('email', 'new@example.com')
        ->call('save')
        ->assertHasErrors(['email']);

    $user = $this->user->fresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->not->toBe('new@example.com');
});

it('allows platform admin to change email', function () {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin,
        'email' => 'super@example.com',
    ]);

    actingAs($superAdmin);

    Livewire::test(Account::class)
        ->set('name', 'Super Admin')
        ->set('email', 'newsuper@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $user = $superAdmin->fresh();

    expect($user->name)->toBe('Super Admin');
    expect($user->email)->toBe('newsuper@example.com');
});
