<?php

use App\Enums\CampaignStatus;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('displays the Actions tab on the campaign edit page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee($campaign->title)
        ->assertSee('Actions')
        ->assertSee('Archive Campaign')
        ->assertSee('Duplicate Campaign');
});

it('can archive a campaign from the Actions tab', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('archive');

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Archived);
});

it('can duplicate a campaign from the Actions tab', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Original Campaign',
        'status' => CampaignStatus::Active,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('duplicate');

    $this->assertDatabaseHas('campaigns', [
        'title' => 'Original Campaign (Copy)',
        'status' => CampaignStatus::Draft->value,
        'organization_id' => $organization->id,
    ]);
});

it('can delete a campaign from the Actions tab', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
    ]);

    $this->actingAs($user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('delete');

    $this->assertDatabaseMissing('campaigns', [
        'id' => $campaign->id,
    ]);
});
