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
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'allow_recurring' => true,
        'config' => [],
    ]);
});

it('saves monthly upsell tiers into the campaign config', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->set('upsell_enabled', true)
        ->set('upsell_cooldown_days', 14)
        ->set('upsell_tiers', [
            ['min' => 50, 'max' => 199, 'offers' => [
                ['type' => 'percent', 'value' => 33],
                ['type' => 'percent', 'value' => 50],
            ]],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $config = $this->campaign->fresh()->config['monthly_upsell'];

    expect($config['enabled'])->toBeTrue()
        ->and($config['cooldown_days'])->toBe(14)
        ->and((float) $config['tiers'][0]['min'])->toBe(50.0)
        ->and($config['tiers'][0]['offers'])->toHaveCount(2);
});

it('rejects overlapping tiers', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->set('upsell_enabled', true)
        ->set('upsell_tiers', [
            ['min' => 50, 'max' => 199, 'offers' => [['type' => 'percent', 'value' => 33]]],
            ['min' => 150, 'max' => 400, 'offers' => [['type' => 'percent', 'value' => 20]]],
        ])
        ->call('save')
        ->assertHasErrors('upsell_tiers');

    expect($this->campaign->fresh()->config)->not->toHaveKey('monthly_upsell');
});

it('skips tier validation when the upsell is disabled', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->set('upsell_enabled', false)
        ->set('upsell_tiers', [
            ['min' => 0, 'max' => 10, 'offers' => []],
        ])
        ->call('save')
        ->assertHasNoErrors();
});

it('seeds a starter tier when the upsell is switched on', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->set('upsell_enabled', true);

    expect($component->get('upsell_tiers'))->toHaveCount(1)
        ->and($component->get('upsell_tiers')[0]['min'])->toBe(50.0)
        ->and($component->get('upsell_tiers')[0]['max'])->toBeNull()
        ->and($component->get('upsell_tiers')[0]['offers'])->toHaveCount(2);
});

it('leaves configured tiers alone when the upsell is switched back on', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->set('upsell_tiers', [
            ['min' => 100, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 30]]],
        ])
        ->set('upsell_enabled', true)
        ->assertCount('upsell_tiers', 1)
        ->assertSet('upsell_tiers.0.min', 100);
});

it('rejects an enabled upsell that has no tiers', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->set('upsell_enabled', true)
        ->set('upsell_tiers', [])
        ->call('save')
        ->assertHasErrors('upsell_tiers');

    expect($this->campaign->fresh()->config)->not->toHaveKey('monthly_upsell');
});

it('adds and removes tiers', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->set('upsell_tiers', [])
        ->call('addUpsellTier');

    expect($component->get('upsell_tiers'))->toHaveCount(1);

    $component->call('removeUpsellTier', 0);

    expect($component->get('upsell_tiers'))->toHaveCount(0);
});

it('renders the tier editor and summary card when the upsell is enabled', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->call('editMonthlyUpsell')
        ->assertSet('activeTab', 'checkout')
        ->assertSet('checkoutPanel', 'upsell')
        ->set('upsell_enabled', true)
        ->set('upsell_tiers', [
            ['min' => 50, 'max' => 199, 'offers' => [['type' => 'percent', 'value' => 33]]],
            ['min' => 200, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 100]]],
        ])
        ->assertSee('Offer a monthly plan to one-time donors')
        ->assertSee('Decline cooldown (days)')
        ->assertSee('Add tier')
        // The summary card renders each tier, including the open-ended one.
        ->assertSee('MYR 50&ndash;199', false)
        ->assertSee('MYR 200&ndash;no limit', false);
});

it('shows a worked example of what each tier would offer', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->call('editMonthlyUpsell')
        ->set('upsell_enabled', true)
        ->set('upsell_tiers', [
            ['min' => 50, 'max' => 200, 'offers' => [['type' => 'percent', 'value' => 50]]],
        ])
        ->assertSee('What donors would see')
        ->assertSee('MYR 125 one-time')
        // 50% of 125 is 62.50, which rounds up to the nearest 5.
        ->assertSee('MYR 125/month or MYR 65/month');
});

it('warns when a tier leaves donors without a lighter option', function () {
    $this->campaign->update(['minimum_amount' => 60]);

    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign->fresh()])
        ->call('editMonthlyUpsell')
        ->set('upsell_enabled', true)
        ->set('upsell_tiers', [
            // 10% of every sampled amount lands under the campaign minimum.
            ['min' => 50, 'max' => 200, 'offers' => [['type' => 'percent', 'value' => 10]]],
        ])
        ->assertSee('Some amounts get no lighter option');
});

it('explains the feature before any tier is configured', function () {
    Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign])
        ->call('editMonthlyUpsell')
        ->assertSee('asking whether they would like to give that amount every month instead')
        ->assertSee('Declining takes them straight to checkout');
});

it('loads existing tiers when mounting', function () {
    $this->campaign->update(['config' => ['monthly_upsell' => [
        'enabled' => true,
        'cooldown_days' => 7,
        'heading' => 'Jadi penyokong bulanan',
        'tiers' => [['min' => 100, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 30]]]],
    ]]]);

    $component = Livewire::actingAs($this->user)
        ->test(CampaignEdit::class, ['campaign' => $this->campaign->fresh()]);

    expect($component->get('upsell_enabled'))->toBeTrue()
        ->and($component->get('upsell_cooldown_days'))->toBe(7)
        ->and($component->get('upsell_heading'))->toBe('Jadi penyokong bulanan')
        ->and($component->get('upsell_tiers'))->toHaveCount(1);
});
