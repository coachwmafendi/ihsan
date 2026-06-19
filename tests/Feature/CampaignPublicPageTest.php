<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Organization;

it('displays an active campaign public page', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertSuccessful()
        ->assertSee($campaign->title);
});

it('returns a 404 for an inactive campaign', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Draft,
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertNotFound();
});

it('renders the donation form on the public page', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => true,
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertSuccessful()
        ->assertSee('Name')
        ->assertSee('Email');
});

it('allows public access when checkout modal is disabled', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => false,
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertSuccessful()
        ->assertSee($campaign->title)
        ->assertSee('Name');
});
