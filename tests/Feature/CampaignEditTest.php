<?php

declare(strict_types=1);

use App\Enums\ElementType;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Element;
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
        ->assertSee('Test Campaign')
        ->assertSee('Active')
        ->assertSee('ID '.$campaign->public_id)
        ->assertSee('Comment')
        ->assertSee('Phone');
});

it('shows a copied tooltip instead of a toast when copying from the edit page', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Copy Tooltip Campaign',
    ]);

    $this->actingAs($this->user)
        ->get("/app/campaigns/{$campaign->public_id}/edit")
        ->assertOk()
        ->assertSee('Copy ID', false)
        ->assertSee('Copied', false)
        ->assertDontSee('Campaign ID copied', false);
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
        ->set('allow_cover_fee', false)
        ->call('save')
        ->assertDispatched('notify');

    $campaign->refresh();
    expect($campaign->title)->toBe('Updated Title')
        ->and($campaign->status->value)->toBe('active')
        ->and($campaign->config['allow_cover_fee'] ?? true)->toBeFalse();
});

it('sanitizes target amount to seven whole-number digits and other amounts to five', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'target_amount' => '1234567.89',
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
        ->assertSet('target_amount', '1234567')
        ->assertSet('minimum_amount', '5')
        ->assertSet('default_amount', '50')
        ->assertSet('suggestedOneTime.0.value', 12345)
        ->assertSet('suggestedMonthly.0.value', 12)
        ->set('target_amount', '98765432.10')
        ->assertSet('target_amount', '9876543')
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

it('loads suggested amounts with six values for each frequency', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'config' => [
            'suggested_amounts_by_currency' => [
                'MYR' => [
                    'one_time' => [
                        ['value' => 50, 'label' => ''],
                    ],
                    'monthly' => [
                        ['value' => 30, 'label' => ''],
                    ],
                ],
            ],
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertCount('suggestedOneTime', 6)
        ->assertCount('suggestedMonthly', 6)
        ->assertSet('suggestedOneTime.0.value', 50)
        ->assertSet('suggestedMonthly.0.value', 30)
        ->assertSet('suggestedOneTime.2.value', 300)
        ->assertSet('suggestedMonthly.2.value', 150)
        ->assertSee('500')
        ->assertSee('300');
});

it('backfills empty suggested amounts with defaults on save', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('suggestedOneTime.0.value', '')
        ->set('suggestedOneTime.2.value', '0')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $campaign->refresh();

    $savedOneTime = collect($campaign->suggested_amounts_one_time ?? [])
        ->map(fn (array $item) => (int) $item['value'])
        ->values()
        ->all();

    // Index 0 was cleared -> defaults to 500, index 1 kept 50, index 2 cleared -> defaults to 300
    expect($savedOneTime)->toBe([500, 50, 300, 200, 100, 50]);
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

it('accepts a target amount up to seven digits', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('has_target', true)
        ->set('target_amount', '9876543')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $campaign->refresh();

    expect($campaign->target_amount)->toBe('9876543.00');
});

it('saves comment and phone visibility settings', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('show_comment', false)
        ->set('show_phone', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $campaign->refresh();

    expect($campaign->config['show_comment'] ?? true)->toBeFalse()
        ->and($campaign->config['show_phone'] ?? true)->toBeFalse();
});

it('hides the phone field when show_phone is disabled', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => 'active',
        'checkout_modal_enabled' => true,
        'config' => [
            'show_phone' => false,
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(DonationForm::class, ['campaign' => $campaign])
        ->assertSee('Email')
        ->assertDontSee('Phone');
});

it('hides the comment field when show_comment is disabled', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => 'active',
        'checkout_modal_enabled' => true,
        'config' => [
            'show_comment' => false,
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(DonationForm::class, ['campaign' => $campaign])
        ->assertSee('Email')
        ->assertDontSee('Comment')
        ->assertDontSee('Leave a message');
});

it('displays saved preset amounts in the donation form', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'suggested_amounts' => null,
        'suggested_amounts_one_time' => [
            ['value' => 500, 'label' => ''],
            ['value' => 400, 'label' => ''],
        ],
        'suggested_amounts_monthly' => [
            ['value' => 100, 'label' => ''],
            ['value' => 75, 'label' => ''],
        ],
    ]);

    $element = Element::factory()->for($this->organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $this->actingAs($this->user);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('selectFrequency', 'one_time')
        ->assertSet('amount', 500)
        ->call('selectFrequency', 'monthly')
        ->assertSet('amount', 100);
});

it('archives a campaign', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->call('archive')
        ->assertRedirect(route('app.campaigns.index'));

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
