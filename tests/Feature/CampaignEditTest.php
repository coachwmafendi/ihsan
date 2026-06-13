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
        ->assertDispatched('notify');

    $campaign->refresh();
    expect($campaign->title)->toBe('Updated Title')
        ->and($campaign->status->value)->toBe('active');
});

it('sanitizes campaign amount inputs to five whole-number digits while editing', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'target_amount' => '12345.67',
        'minimum_amount' => '5.00',
        'config' => [
            'default_amount' => '50.00',
        ],
        'suggested_amounts_one_time' => [
            ['value' => '123456.78', 'label' => ''],
        ],
        'suggested_amounts_monthly' => [
            ['value' => '12.99', 'label' => ''],
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSet('target_amount', '12345')
        ->assertSet('minimum_amount', '5')
        ->assertSet('default_amount', '50')
        ->assertSet('suggestedOneTime.0.value', 12345)
        ->assertSet('suggestedMonthly.0.value', 12)
        ->set('target_amount', '987654.32')
        ->assertSet('target_amount', '98765')
        ->set('minimum_amount', 'RM 12,345')
        ->assertSet('minimum_amount', '12')
        ->set('default_amount', '10.99')
        ->assertSet('default_amount', '10')
        ->set('newOneTimeValue', '123456')
        ->assertSet('newOneTimeValue', '12345')
        ->set('suggestedOneTime.0.value', '54321.99')
        ->assertSet('suggestedOneTime.0.value', 54321)
        ->set('suggestedMonthly.0.value', '654321')
        ->assertSet('suggestedMonthly.0.value', 65432);
});

it('saves decimal campaign amount inputs as whole numbers', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('target_amount', '123.45')
        ->set('minimum_amount', '5.50')
        ->set('default_amount', '10.99')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $campaign->refresh();

    expect($campaign->target_amount)->toBe('123.00')
        ->and($campaign->minimum_amount)->toBe('5.00')
        ->and($campaign->config['default_amount'])->toBe(10);
});

it('archives a campaign', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('archive')
        ->assertDispatched('notify');

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
