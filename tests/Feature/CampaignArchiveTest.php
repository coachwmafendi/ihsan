<?php

use App\Enums\CampaignStatus;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Livewire\App\Campaigns\CampaignIndex;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('excludes archived campaigns from the default index listing', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['organization_id' => $organization->id]);

    $active = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Active]);
    $archived = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Archived]);

    \Livewire\Livewire::actingAs($user)
        ->test(CampaignIndex::class)
        ->assertSee($active->title)
        ->assertDontSee($archived->title);
});

it('shows archived campaigns when archived filter is selected', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['organization_id' => $organization->id]);

    $archived = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Archived]);

    \Livewire\Livewire::actingAs($user)
        ->test(CampaignIndex::class)
        ->set('statusFilter', 'archived')
        ->assertSee($archived->title);
});

it('archives campaign and redirects to index', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create(['organization_id' => $organization->id]);
    $campaign = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Active]);

    \Livewire\Livewire::actingAs($user)
        ->test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('archive')
        ->assertRedirect(route('app.campaigns.index'));

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Archived);
});
