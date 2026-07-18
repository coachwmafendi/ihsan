<?php

declare(strict_types=1);

use App\Enums\ElementType;
use App\Enums\UserRole;
use App\Livewire\App\Campaigns\CampaignEdit;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    $this->get("https://app.example.test/campaigns/{$campaign->public_id}/edit")
        ->assertRedirect(route('login'));
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
        ->get("https://app.example.test/campaigns/{$campaign->public_id}/edit")
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

it('preserves newlines in the campaign page message', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Test Campaign',
    ]);

    $this->actingAs($this->user);

    $message = "First line.\n\nSecond line.\nThird line.";

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('contentMessage', $message)
        ->call('save')
        ->assertHasNoErrors('contentMessage')
        ->assertDispatched('notify');

    $campaign->refresh();
    expect($campaign->config['content_message'] ?? '')->toBe($message)
        ->and($campaign->config['content_message'] ?? '')->toContain("\n");
});

it('toggles has_target and has_end_date on and off', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'has_target' => false,
        'has_end_date' => false,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSet('has_target', false)
        ->assertSet('has_end_date', false)
        ->call('toggleHasTarget')
        ->assertSet('has_target', true)
        ->call('toggleHasEndDate')
        ->assertSet('has_end_date', true)
        ->call('toggleHasTarget')
        ->assertSet('has_target', false)
        ->call('toggleHasEndDate')
        ->assertSet('has_end_date', false);
});

it('accepts a new campaign image for preview', function () {
    Storage::fake('public');

    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('image', UploadedFile::fake()->image('campaign.jpg'))
        ->assertHasNoErrors('image');
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

it('saves the campaign page enabled setting', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'campaign_page_enabled' => true,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSet('campaign_page_enabled', true)
        ->set('campaign_page_enabled', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $campaign->refresh();
    expect($campaign->campaign_page_enabled)->toBeFalse();
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

it('displays a configuration snapshot on the overview tab', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'has_target' => true,
        'target_amount' => 10000,
        'has_end_date' => true,
        'end_date' => now()->addMonth(),
        'allow_recurring' => true,
        'allow_custom_amount' => true,
        'minimum_amount' => 5,
        'thank_you_message' => 'Thank you for your support!',
        'redirect_url' => 'https://example.com/thank-you',
        'campaign_page_enabled' => true,
        'config' => [
            'default_frequency' => 'one_time',
            'default_amount' => 50,
            'default_currency' => 'MYR',
            'currency_autodetect' => false,
            'allow_cover_fee' => true,
            'show_comment' => true,
            'show_phone' => true,
            'show_total_raised' => true,
            'post_donation_mode' => 'default',
            'share_channels' => ['facebook', 'x', 'linkedin', 'email'],
            'share_message' => 'Support our campaign!',
            'content_title' => 'Campaign Page Title',
            'content_message' => 'This is the campaign page message.',
            'suggested_amounts_by_currency' => [
                'MYR' => [
                    'one_time' => [
                        ['value' => 500, 'label' => ''],
                        ['value' => 100, 'label' => ''],
                    ],
                    'monthly' => [
                        ['value' => 300, 'label' => ''],
                    ],
                ],
            ],
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('Configuration')
        ->assertSee('Goal & Duration')
        ->assertSee('MYR 10,000')
        ->assertSee('Donation Options')
        ->assertSee('Recurring on')
        ->assertSee('Custom amount on')
        ->assertSee('Min MYR 5')
        ->assertSee('Checkout Defaults')
        ->assertSee('One time')
        ->assertSee('MYR 50')
        ->assertSee('MYR')
        ->assertSee('Off')
        ->assertSee('Checkout Fields')
        ->assertSee('Cover fee on')
        ->assertSee('Comment on')
        ->assertSee('Phone on')
        ->assertSee('Suggested Amounts')
        ->assertSee('500')
        ->assertSee('300')
        ->assertSee('Campaign Page')
        ->assertSee('Enabled')
        ->assertSee('Public URL')
        ->assertSee(route('campaigns.public', $campaign->public_id))
        ->assertSee('Campaign Page Title')
        ->assertSee('This is the campaign page message.')
        ->assertSee('Show Total Raised')
        ->assertSee('On')
        ->assertSee('Post-Donation Experience')
        ->assertSee('Default thank-you screen')
        ->assertSee('Sharing Channels')
        ->assertSee('Facebook')
        ->assertSee('LinkedIn')
        ->assertSee('Default Sharing Message')
        ->assertSee('Support our campaign!')
        ->assertSee('Edit Campaign Page')
        ->assertSee("wire:click=\"\$set('activeTab', 'campaign-page')\"", false)
        ->call('$set', 'activeTab', 'campaign-page')
        ->assertSet('activeTab', 'campaign-page');
});

it('shows the MYR-converted amount for a recent donation made in a foreign currency', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'currency' => 'usd',
        'gross_amount' => 25.00,
        'base_amount' => 115.23,
        'exchange_rate' => 4.6092,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('MYR 115.23')
        ->assertSee('≈ MYR')
        ->assertDontSee('RM 25.00', false);
});

it('shows amounts raised per checkout channel on the overview tab', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    Donation::factory()->create(['campaign_id' => $campaign->id, 'source' => 'campaign_page', 'base_amount' => 150, 'gross_amount' => 150]);
    Donation::factory()->create(['campaign_id' => $campaign->id, 'source' => 'checkout_modal', 'base_amount' => 50, 'gross_amount' => 50]);
    Donation::factory()->create(['campaign_id' => $campaign->id, 'source' => 'virtual_terminal', 'base_amount' => 200, 'gross_amount' => 200]);
    Donation::factory()->create(['campaign_id' => $campaign->id, 'source' => 'element', 'base_amount' => 25, 'gross_amount' => 25]);
    Donation::factory()->create(['campaign_id' => $campaign->id, 'source' => null, 'base_amount' => 10, 'gross_amount' => 10]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSeeInOrder(['Total raised', 'Checkout Modal', 'Campaign Page', 'Virtual Terminal', 'Recurring plans', 'Last donation'])
        ->assertSee('MYR 85')
        ->assertSee('MYR 150')
        ->assertSee('MYR 200')
        ->assertDontSee('≈');
});

it('shows fallback campaign page content title on the overview tab', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Fallback Campaign Title',
        'campaign_page_enabled' => true,
        'config' => [
            'content_title' => null,
            'content_message' => null,
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('Fallback Campaign Title')
        ->assertSee('(fallback)')
        ->assertSeeText('Not set');
});

it('shows campaign page disabled state on the overview tab', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'campaign_page_enabled' => false,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('Campaign Page')
        ->assertSee('Disabled')
        ->assertSee('Campaign Page is disabled')
        ->assertSee('Enable Campaign Page')
        ->assertSee("wire:click=\"\$set('activeTab', 'settings')\"", false)
        ->call('$set', 'activeTab', 'settings')
        ->assertSet('activeTab', 'settings');
});

it('displays default configuration values when fields are empty', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'has_target' => false,
        'has_end_date' => false,
        'thank_you_message' => null,
        'redirect_url' => null,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('No target')
        ->assertSee('No end date');
});

it('rejects a description with more than 200 words', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    $longDescription = implode(' ', array_fill(0, 201, 'word'));

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('/ 200 words')
        ->set('description', $longDescription)
        ->call('save')
        ->assertHasErrors(['description']);
});

it('accepts a description with exactly 200 words', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    $validDescription = implode(' ', array_fill(0, 200, 'word'));

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->set('description', $validDescription)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $campaign->refresh();
    expect($campaign->description)->toBe($validDescription);
});

it('falls back to legacy suggested amounts when config is empty', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'config' => ['default_currency' => 'MYR'],
        'suggested_amounts_one_time' => [
            ['value' => 123, 'label' => ''],
        ],
        'suggested_amounts_monthly' => [
            ['value' => 45, 'label' => ''],
        ],
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('123')
        ->assertSee('45');
});

it('provides quick edit links to settings and checkout tabs', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->actingAs($this->user);

    Livewire::test(CampaignEdit::class, ['campaign' => $campaign])
        ->assertSee('Edit Settings')
        ->assertSee('Edit Checkout')
        ->assertSee("wire:click=\"\$set('activeTab', 'settings')\"", false)
        ->assertSee("wire:click=\"\$set('activeTab', 'checkout')\"", false);
});
