<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'role' => UserRole::NgoAdmin,
    ]);
});

it('renders campaign page content panel with required labels', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('activeTab', 'campaign-page')
        ->assertSee('Content')
        ->assertSee('Logo')
        ->assertSee('Image')
        ->assertSee('Title')
        ->assertSee('Message')
        ->assertSee('200 words');
});

it('renders campaign page thank you panel with required labels', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('activeTab', 'campaign-page')
        ->set('campaignPagePanel', 'thank-you')
        ->assertSee('Thank you screen')
        ->assertSee('Show supporters the default thank you screen')
        ->assertSee('Redirect supporters to a specific URL')
        ->assertSee('Sharing URL')
        ->assertSee('Default sharing message');
});

it('renders campaign page progress panel with required controls', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('activeTab', 'campaign-page')
        ->set('campaignPagePanel', 'progress')
        ->assertSee('Campaign progress')
        ->assertSee('Show total raised amount')
        ->assertSee('Add campaign goal')
        ->assertSee('Set end date');
});

it('persists campaign page settings on save', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('postDonationMode', 'redirect')
        ->set('thank_you_message', 'Thanks!')
        ->set('redirect_url', 'https://example.com/thanks')
        ->set('shareChannels', ['facebook', 'email'])
        ->set('shareMessage', 'Support this campaign!')
        ->call('save');

    $campaign->refresh();

    expect($campaign->config)
        ->post_donation_mode->toBe('redirect')
        ->share_channels->toBe(['facebook', 'email'])
        ->share_message->toBe('Support this campaign!')
        ->and($campaign->thank_you_message)->toBe('Thanks!')
        ->and($campaign->redirect_url)->toBe('https://example.com/thanks');
});

it('persists campaign page content settings on save', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('contentTitle', 'Help us build a school')
        ->set('contentMessage', 'Your support makes a difference.')
        ->call('save');

    $campaign->refresh();

    expect($campaign->config)
        ->content_title->toBe('Help us build a school')
        ->content_message->toBe('Your support makes a difference.');
});

it('blocks content message longer than 200 words', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    $longMessage = implode(' ', array_fill(0, 201, 'word'));

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('contentMessage', $longMessage)
        ->call('save')
        ->assertHasErrors(['contentMessage']);
});

it('persists campaign page progress settings on save', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'config' => [],
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('show_total_raised', false)
        ->set('has_target', true)
        ->set('target_amount', '5000')
        ->set('has_end_date', true)
        ->set('end_date', '2026-12-31')
        ->call('save');

    $campaign->refresh();

    expect($campaign->config)->show_total_raised->toBeFalse();
    expect($campaign)
        ->has_target->toBeTrue()
        ->target_amount->toBeNumeric()->toEqual(5000)
        ->has_end_date->toBeTrue();
});

it('treats the public campaign page donation form as non-embed so redirect can fire', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'redirect_url' => 'https://example.com/thanks',
        'config' => ['post_donation_mode' => 'redirect'],
    ]);

    Livewire::test(DonationForm::class, ['campaign' => $campaign, 'isPublicPage' => true])
        ->assertSet('isEmbed', false)
        ->assertSee('https:\/\/example.com\/thanks', false);
});

it('does not expose the redirect URL to the donation form when redirect mode is disabled', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'redirect_url' => 'https://example.com/thanks',
        'config' => ['post_donation_mode' => 'default'],
    ]);

    Livewire::test(DonationForm::class, ['campaign' => $campaign, 'isPublicPage' => true])
        ->assertSet('isEmbed', false)
        ->assertDontSee('https://example.com/thanks', false);
});
