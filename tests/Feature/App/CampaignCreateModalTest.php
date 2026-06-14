<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses()->group('campaigns');
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('starts with the modal hidden', function () {
    Livewire::actingAs($this->user)
        ->test('app.campaigns.campaign-create-modal')
        ->assertSet('showCreateModal', false)
        ->assertDontSee('Create a new campaign');
});

it('opens the modal when the open event is dispatched', function () {
    Livewire::actingAs($this->user)
        ->test('app.campaigns.campaign-create-modal')
        ->dispatch('open-create-campaign-modal')
        ->assertSet('showCreateModal', true)
        ->assertSee('Create a new campaign');
});

it('creates a new campaign from the modal and redirects to edit', function () {
    Livewire::actingAs($this->user)
        ->test('app.campaigns.campaign-create-modal')
        ->set('showCreateModal', true)
        ->set('createMode', 'new')
        ->set('newCampaignName', 'My New Campaign')
        ->call('createCampaign')
        ->assertHasNoErrors()
        ->assertDispatched('notify')
        ->assertRedirect(route('app.campaigns.edit', Campaign::where('title', 'My New Campaign')->first()));

    expect(Campaign::where('title', 'My New Campaign')->exists())->toBeTrue();
});

it('clones an existing campaign from the modal', function () {
    $source = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Source Campaign',
        'allow_recurring' => false,
        'config' => ['allow_cover_fee' => false],
    ]);

    Livewire::actingAs($this->user)
        ->test('app.campaigns.campaign-create-modal')
        ->set('showCreateModal', true)
        ->set('createMode', 'clone')
        ->set('cloneCampaignId', $source->id)
        ->set('newCampaignName', 'Cloned Campaign')
        ->call('createCampaign')
        ->assertHasNoErrors()
        ->assertRedirect(route('app.campaigns.edit', Campaign::where('title', 'Cloned Campaign')->first()));

    $clone = Campaign::where('title', 'Cloned Campaign')->first();
    expect($clone)->not->toBeNull();
    expect((bool) $clone->allow_recurring)->toBe($source->allow_recurring);
    expect($clone->config)->toBe($source->config);
});

it('blocks cloning a campaign from another organization', function () {
    $otherOrg = Organization::factory()->create();
    $source = Campaign::factory()->create([
        'organization_id' => $otherOrg->id,
        'title' => 'Other Org Campaign',
    ]);

    Livewire::actingAs($this->user)
        ->test('app.campaigns.campaign-create-modal')
        ->set('showCreateModal', true)
        ->set('createMode', 'clone')
        ->set('cloneCampaignId', $source->id)
        ->set('newCampaignName', 'Cloned Campaign')
        ->call('createCampaign')
        ->assertDispatched('notify');

    expect(Campaign::where('title', 'Cloned Campaign')->exists())->toBeFalse();
});

it('shows a validation error when the campaign name is blank', function () {
    Livewire::actingAs($this->user)
        ->test('app.campaigns.campaign-create-modal')
        ->set('showCreateModal', true)
        ->set('newCampaignName', '')
        ->call('createCampaign')
        ->assertHasErrors(['newCampaignName']);
});

it('clears the auto-populated name when clone selection is removed', function () {
    $source = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Source Campaign',
    ]);

    Livewire::actingAs($this->user)
        ->test('app.campaigns.campaign-create-modal')
        ->set('cloneCampaignId', $source->id)
        ->assertSet('newCampaignName', 'Source Campaign (Copy)')
        ->set('cloneCampaignId', '')
        ->assertSet('newCampaignName', '');
});
