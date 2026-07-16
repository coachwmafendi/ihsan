<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Support\Facades\URL;

it('renders default preset options on the increase page', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 30,
        'currency' => 'usd',
    ]);

    $url = URL::temporarySignedRoute(
        'donorportal.subscriptions.increase-link',
        now()->addMinutes(5),
        ['organization' => $organization, 'subscription' => $subscription],
    );

    $this->get($url)
        ->assertOk()
        ->assertSee('+ USD 5')
        ->assertSee('+ USD 80')
        ->assertSee('+ USD 100')
        ->assertSee('USD 35.00', false)
        ->assertSee('USD 110.00', false)
        ->assertSee('USD 130.00', false);
});

it('uses increments from the email chip link and preselects the selected value', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 30,
        'currency' => 'usd',
    ]);

    $url = URL::temporarySignedRoute(
        'donorportal.subscriptions.increase-link',
        now()->addMinutes(5),
        [
            'organization' => $organization,
            'subscription' => $subscription,
            'increments' => '15,25,35',
            'selected' => '25',
        ],
    );

    $this->get($url)
        ->assertOk()
        ->assertSee('+ USD 15')
        ->assertSee('+ USD 25')
        ->assertSee('+ USD 35')
        ->assertDontSee('+ USD 5')
        ->assertDontSee('+ USD 80')
        ->assertDontSee('+ USD 100')
        ->assertSee('USD 45.00', false)
        ->assertSee('USD 55.00', false)
        ->assertSee('USD 65.00', false);
});

it('falls back to defaults when invalid increments are provided', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'currency' => 'usd',
    ]);

    $url = URL::temporarySignedRoute(
        'donorportal.subscriptions.increase-link',
        now()->addMinutes(5),
        [
            'organization' => $organization,
            'subscription' => $subscription,
            'increments' => '0,-10,abc',
            'selected' => '99',
        ],
    );

    $this->get($url)
        ->assertOk()
        ->assertSee('+ USD 5')
        ->assertSee('+ USD 80')
        ->assertSee('+ USD 100');
});
