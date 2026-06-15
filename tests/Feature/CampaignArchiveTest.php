<?php

use App\Enums\CampaignStatus;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Livewire\App\Campaigns\CampaignIndex;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('excludes archived campaigns from the default index listing', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['organization_id' => $organization->id]);

    $active = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Active]);
    $archived = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Archived]);

    Livewire::actingAs($user)
        ->test(CampaignIndex::class)
        ->assertSee($active->title)
        ->assertDontSee($archived->title);
});

it('shows archived campaigns when showArchived is toggled', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['organization_id' => $organization->id]);

    $archived = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Archived]);

    Livewire::actingAs($user)
        ->test(CampaignIndex::class)
        ->call('toggleArchived')
        ->assertSee($archived->title);
});

it('archives campaign and redirects to index', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['organization_id' => $organization->id]);
    $campaign = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Active]);

    Livewire::actingAs($user)
        ->test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('archive')
        ->assertRedirect(route('app.campaigns.index'));

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Archived);
});

it('can restore an archived campaign to draft', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Archived]);

    expect($campaign->status)->toBe(CampaignStatus::Archived);

    $campaign->restore();

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Draft);
});

it('can restore an archived campaign from the index', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($org)->create([
        'status' => CampaignStatus::Archived,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class)
        ->call('restore', $campaign->public_id)
        ->assertDispatched('notify');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Draft);
});

it('cannot restore a campaign belonging to another org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $user = User::factory()->for($org)->create();
    $campaign = Campaign::factory()->for($otherOrg)->create([
        'status' => CampaignStatus::Archived,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignIndex::class)
        ->call('restore', $campaign->public_id)
        ->assertForbidden();
});

it('toggleArchived switches between active and archived campaigns', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->for($org)->create();

    $active = Campaign::factory()->for($org)->create(['status' => CampaignStatus::Active]);
    $archived = Campaign::factory()->for($org)->create(['status' => CampaignStatus::Archived]);

    $this->actingAs($user);

    $component = Livewire::test(CampaignIndex::class);

    $component->assertSee($active->title)->assertDontSee($archived->title);

    $component->call('toggleArchived')
        ->assertSee($archived->title)
        ->assertDontSee($active->title);
});
