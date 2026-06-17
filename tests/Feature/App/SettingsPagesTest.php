<?php

declare(strict_types=1);

use App\Livewire\App\Settings\AllowDomains;
use App\Livewire\App\Settings\DonorPortal;
use App\Livewire\App\Settings\Profile;
use App\Models\Organization;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->organization = Organization::factory()->stripeConnected()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

// Settings Profile
it('renders settings profile page', function () {
    actingAs($this->user)
        ->get('/app/settings/profile')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Profile');
});

it('redirects guests from settings profile', function () {
    get('/app/settings/profile')->assertRedirect('/login');
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

it('defaults donor portal reply-to email to organisation contact email', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'contact_email' => 'contact@example.com',
        'settings' => [],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    Livewire::test(DonorPortal::class)
        ->assertSet('portal_reply_to_email', 'contact@example.com');
});
