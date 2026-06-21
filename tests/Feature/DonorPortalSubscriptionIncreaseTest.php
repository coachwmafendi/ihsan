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
        ->assertSee('+ $5')
        ->assertSee('+ $80')
        ->assertSee('+ $100')
        ->assertSee('$ 35.00 USD', false)
        ->assertSee('$ 110.00 USD', false)
        ->assertSee('$ 130.00 USD', false);
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
        ->assertSee('+ $15')
        ->assertSee('+ $25')
        ->assertSee('+ $35')
        ->assertDontSee('+ $5')
        ->assertDontSee('+ $80')
        ->assertDontSee('+ $100')
        ->assertSee('$ 45.00 USD', false)
        ->assertSee('$ 55.00 USD', false)
        ->assertSee('$ 65.00 USD', false);
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
        ->assertSee('+ $5')
        ->assertSee('+ $80')
        ->assertSee('+ $100');
});
