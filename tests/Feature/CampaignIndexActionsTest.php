<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignIndex;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->for($this->organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'title' => 'Ramadan Fund',
        'status' => CampaignStatus::Active,
    ]);
});

it('dispatches the create-campaign modal event when visited with ?create=1', function () {
    $this->actingAs($this->user);

    Livewire::withQueryParams(['create' => '1'])
        ->test(CampaignIndex::class)
        ->assertDispatched('open-create-campaign-modal');
});

it('does not dispatch the create-campaign modal event without the query param', function () {
    $this->actingAs($this->user);

    Livewire::test(CampaignIndex::class)
        ->assertNotDispatched('open-create-campaign-modal');
});

it('can rename a campaign from the index', function () {
    $this->actingAs($this->user);

    Livewire::test(CampaignIndex::class)
        ->call('openRenameModal', $this->campaign->public_id)
        ->set('renameTitle', 'Updated Fund')
        ->call('saveRename')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    expect($this->campaign->fresh()->title)->toBe('Updated Fund');
});

it('cannot rename a campaign from another organization', function () {
    $otherOrg = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->for($otherOrg)->create();

    $this->actingAs($this->user);

    Livewire::test(CampaignIndex::class)
        ->call('openRenameModal', $otherCampaign->public_id)
        ->assertForbidden();
});

it('can clone a campaign from the index', function () {
    $this->actingAs($this->user);

    Livewire::test(CampaignIndex::class)
        ->call('clone', $this->campaign->public_id)
        ->assertDispatched('notify');

    $copy = Campaign::where('title', 'Ramadan Fund (Copy)')->first();

    expect($copy)->not->toBeNull();
    expect($copy->organization_id)->toBe($this->campaign->organization_id);
    expect($copy->status->value)->toBe('draft');
    expect($copy->collected_amount)->toBe('0.00');
    expect($copy->public_id)->not->toBe($this->campaign->public_id);
    expect($copy->slug)->not->toBe($this->campaign->slug);
    expect($copy->form_parameter)->not->toBe($this->campaign->form_parameter);
});

it('cannot clone a campaign from another organization', function () {
    $otherOrg = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->for($otherOrg)->create();

    $this->actingAs($this->user);

    Livewire::test(CampaignIndex::class)
        ->call('clone', $otherCampaign->public_id)
        ->assertForbidden();

    expect(Campaign::count())->toBe(2);
});

it('can disable a campaign from the index', function () {
    $this->actingAs($this->user);

    expect($this->campaign->status->value)->toBe('active');

    Livewire::test(CampaignIndex::class)
        ->call('disable', $this->campaign->public_id)
        ->assertDispatched('notify');

    expect($this->campaign->fresh()->status->value)->toBe('paused');
});

it('cannot disable a campaign from another organization', function () {
    $otherOrg = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->for($otherOrg)->create([
        'status' => CampaignStatus::Active,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignIndex::class)
        ->call('disable', $otherCampaign->public_id)
        ->assertForbidden();

    expect($otherCampaign->fresh()->status->value)->toBe('active');
});
