<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

uses()->group('chip');

it('renders chip success callback page with postmessage', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $response = $this->get(route('chip.callback', ['donation' => $donation->public_id, 'status' => 'success']));

    $response->assertOk()
        ->assertSee('chip:payment:success')
        ->assertSee($donation->public_id)
        ->assertSee('finalizeUrl')
        ->assertSee('fetch(finalizeUrl');
});

it('renders chip failure callback page with postmessage', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $response = $this->get(route('chip.callback', ['donation' => $donation->public_id, 'status' => 'failure']));

    $response->assertOk()
        ->assertSee('chip:payment:failure')
        ->assertSee($donation->public_id);
});

it('renders chip cancelled callback page with postmessage', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $response = $this->get(route('chip.callback', ['donation' => $donation->public_id, 'status' => 'cancelled']));

    $response->assertOk()
        ->assertSee('chip:payment:cancel')
        ->assertSee($donation->public_id);
});

it('rejects invalid callback status', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $this->get(route('chip.callback', ['donation' => $donation->public_id, 'status' => 'invalid']))
        ->assertNotFound();
});

it('includes return_to in the redirect fallback for successful payments', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();
    $returnTo = 'https://chip-integration.test/donate/TOKEN?popup=1';

    $response = $this->get(route('chip.callback', [
        'donation' => $donation->public_id,
        'status' => 'success',
        'return_to' => $returnTo,
    ]));

    $response->assertOk()
        ->assertSee('TOKEN?popup=1')
        ->assertSee('buildReturnUrl(returnTo)')
        ->assertSee("'chip_status='", false);
});

it('includes return_to in the redirect fallback for cancelled payments', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();
    $returnTo = 'https://chip-integration.test/donate/TOKEN?popup=1';

    $response = $this->get(route('chip.callback', [
        'donation' => $donation->public_id,
        'status' => 'cancelled',
        'return_to' => $returnTo,
    ]));

    $response->assertOk()
        ->assertSee('TOKEN?popup=1')
        ->assertSee("'chip_status='", false);
});
