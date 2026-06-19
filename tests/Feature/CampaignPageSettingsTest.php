<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignEdit;
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

it('renders campaign page settings with required labels', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('activeTab', 'campaign-page')
        ->assertSee('Campaign Page')
        ->assertSee('Thank you screen')
        ->assertSee('Show supporters the default thank you screen')
        ->assertSee('Redirect supporters to a specific URL')
        ->assertSee('Sharing URL')
        ->assertSee('Default sharing message');
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
