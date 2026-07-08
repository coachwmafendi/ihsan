<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Organization;
use Livewire\Livewire;

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

it('renders share buttons on the donation form success step', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => true,
        'config' => [
            'post_donation_mode' => 'default',
            'share_channels' => ['facebook', 'x', 'linkedin', 'email'],
            'share_message' => 'Please support this campaign',
        ],
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertSuccessful()
        ->assertSee('Share this campaign')
        ->assertSee('https://www.facebook.com/sharer/sharer.php?u=');
});

it('hides share buttons when post donation mode is redirect', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => true,
        'config' => [
            'post_donation_mode' => 'redirect',
            'share_channels' => ['facebook', 'x', 'linkedin', 'email'],
        ],
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertSuccessful()
        ->assertDontSee('Share this campaign');
});

it('returns a 404 when the campaign page is disabled', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'campaign_page_enabled' => false,
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertNotFound();
});

it('shows a celebratory callout when the campaign exceeds its target', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'target_amount' => 10000.00,
        'collected_amount' => 10500.00,
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertSuccessful()
        ->assertSee('Goal smashed!')
        ->assertSee('We crossed the target');
});

it('does not show the celebratory callout before the target is reached', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'target_amount' => 10000.00,
        'collected_amount' => 5000.00,
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertSuccessful()
        ->assertDontSee('Goal smashed!');
});

it('refreshes campaign totals when a donation is received on the public page', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'target_amount' => 10000.00,
        'collected_amount' => 2000.00,
    ]);

    $component = Livewire::test(CampaignPublicPage::class, ['campaign' => $campaign]);

    $component->assertSet('campaign.collected_amount', 2000.00);

    $campaign->update(['collected_amount' => 3500.00]);

    $component->dispatch('campaign-donation-received', campaignPublicId: $campaign->public_id)
        ->assertSet('campaign.collected_amount', 3500.00);
});

it('polls campaign totals from the database', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'target_amount' => 10000.00,
        'collected_amount' => 2000.00,
    ]);

    $component = Livewire::test(CampaignPublicPage::class, ['campaign' => $campaign]);

    $component->assertSet('campaign.collected_amount', 2000.00);

    $campaign->update(['collected_amount' => 5000.00]);

    $component->call('pollCampaign')
        ->assertSet('campaign.collected_amount', 5000.00);
});

it('passes the campaign public id to the donation form root', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
    ]);

    $this->get('/campaigns/'.$campaign->public_id)
        ->assertSuccessful()
        ->assertSee('data-campaign-public-id="'.$campaign->public_id.'"', false);
});

it('renders milestone labels with uppercase K and RM0 at the start', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'target_amount' => 100000.00,
        'collected_amount' => 13752.31,
    ]);

    $response = $this->get('/campaigns/'.$campaign->public_id);

    $response
        ->assertSuccessful()
        ->assertSee('RM0')
        ->assertSee('RM10K')
        ->assertSee('RM25K')
        ->assertSee('RM50K')
        ->assertSee('RM100K')
        ->assertSee('13.8%')
        ->assertDontSee('RM10k')
        ->assertDontSee('RM25k');
});

it('places the current milestone checkpoint at the next unachieved milestone', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => Organization::factory()->create(),
        'status' => CampaignStatus::Active,
        'target_amount' => 100000.00,
        'collected_amount' => 13752.31,
    ]);

    $progress = Livewire::test(CampaignPublicPage::class, ['campaign' => $campaign])
        ->instance()
        ->progress();

    expect($progress['progressPercent'])
        ->toBeGreaterThan(13.75)
        ->toBeLessThan(13.76);
    expect($progress['currentCheckpointIndex'])->toBe(2);
    expect($progress['currentCheckpointAmount'])->toBe(25000.0);
    expect($progress['leftToNext'])->toBe(11247.69);
    expect($progress['goalReached'])->toBeFalse();
});
