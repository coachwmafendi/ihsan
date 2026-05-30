<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Organization;

it('renders campaign description as html instead of escaped text', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Ramadan Fund',
        'description' => '<p>First paragraph</p><p>Second paragraph</p>',
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => true,
        'form_parameter' => 'RAMADAN2026',
    ]);

    $response = $this->get(route('donations.campaign-show', ['campaign' => $campaign->form_parameter]));

    $response->assertOk();
    $response->assertSee('<p>First paragraph</p>', false);
    $response->assertDontSee('&lt;p&gt;First paragraph&lt;/p&gt;');
});
