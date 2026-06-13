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

it('requires authentication', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->get("/app/campaigns/{$campaign->public_id}/edit")
        ->assertRedirect('/login');
});

it('renders for an authorized user', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Test Campaign',
        'status' => 'active',
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('Edit Campaign')
        ->assertSee('Test Campaign');
});

it('updates a campaign', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Old Title',
        'status' => 'draft',
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('title', 'Updated Title')
        ->set('status', 'active')
        ->call('save')
        ->assertDispatched('toast');

    $campaign->refresh();
    expect($campaign->title)->toBe('Updated Title')
        ->and($campaign->status->value)->toBe('active');
});

it('archives a campaign', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('archive')
        ->assertDispatched('toast');

    $campaign->refresh();
    expect($campaign->status->value)->toBe('archived');
});

it('duplicates a campaign', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Original Campaign',
        'status' => 'active',
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('duplicate');

    expect(Campaign::count())->toBe(2);

    $copy = Campaign::latest('id')->first();
    expect($copy->title)->toBe('Original Campaign (Copy)')
        ->and($copy->status->value)->toBe('draft')
        ->and($copy->organization_id)->toBe($this->organization->id);
});

it('prevents unauthorized access', function () {
    $otherOrg = Organization::factory()->create();
    $campaign = Campaign::factory()->create([
        'organization_id' => $otherOrg->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertForbidden();
});
