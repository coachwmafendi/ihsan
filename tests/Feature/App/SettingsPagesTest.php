<?php

declare(strict_types=1);

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

it('saves allowed domains in profile settings', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => []],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    Livewire::test(Profile::class)
        ->call('addDomain', 'mywebsite.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh()->settings['allowed_domains'])->toContain('mywebsite.com');
});

it('removes a domain from allowed domains in profile settings', function () {
    $organization = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['mywebsite.com', 'other.org']],
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    Livewire::test(Profile::class)
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

    Livewire::test(Profile::class)
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

    $component = Livewire::test(Profile::class);

    foreach (range(1, 10) as $i) {
        $component->call('addDomain', "domain-{$i}.com");
    }

    $component->call('save')->assertHasNoErrors();

    expect($organization->fresh()->settings['allowed_domains'])->toHaveCount(10);

    $component->call('addDomain', 'domain-11.com');

    $component->call('save')->assertHasNoErrors();

    expect($organization->fresh()->settings['allowed_domains'])->toHaveCount(10);
});

it('only shows a saved notification when the profile actually changes', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);

    $component = Livewire::test(Profile::class);

    $component
        ->set('contact_phone', '0123456789')
        ->call('save')
        ->assertDispatched('notify', message: 'Organisation profile saved.', variant: 'success');

    $component
        ->call('save')
        ->assertNotDispatched('notify');
});
