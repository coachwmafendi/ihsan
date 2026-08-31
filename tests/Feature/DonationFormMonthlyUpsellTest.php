<?php

use App\Enums\CampaignStatus;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Organization;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $upsell
 * @param  array<string, mixed>  $attributes
 */
function upsellFormCampaign(array $upsell = [], array $attributes = []): Campaign
{
    $default = [
        'enabled' => true,
        'cooldown_days' => 30,
        'tiers' => [
            [
                'min' => 50,
                'max' => null,
                'offers' => [
                    ['type' => 'percent', 'value' => 33],
                    ['type' => 'percent', 'value' => 50],
                ],
            ],
        ],
    ];

    return Campaign::factory()
        ->for(Organization::factory())
        ->create([
            'status' => CampaignStatus::Active,
            'allow_recurring' => true,
            'checkout_modal_enabled' => true,
            'config' => ['monthly_upsell' => array_replace($default, $upsell)],
            ...$attributes,
        ]);
}

it('resolves the offer for the amount the donor actually picked', function () {
    // The donor picks the amount inside Alpine, so it never reaches a server
    // property before the offer has to be made.
    $component = Livewire::test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->instance()->resolveMonthlyUpsell(120.0)['offers'])->toBe([120.0, 60.0]);
});

it('resolves a different offer as the picked amount changes', function () {
    $component = Livewire::test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->instance()->resolveMonthlyUpsell(300.0)['offers'])->toBe([300.0, 150.0])
        ->and($component->instance()->resolveMonthlyUpsell(20.0))->toBeNull();
});

it('exposes no offer when the campaign has the upsell disabled', function () {
    $component = Livewire::test(DonationForm::class, ['campaign' => upsellFormCampaign(['enabled' => false])]);

    expect($component->instance()->resolveMonthlyUpsell(120.0))->toBeNull();
});

it('exposes no offer when the donor is already giving monthly', function () {
    $component = Livewire::test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->instance()->resolveMonthlyUpsell(120.0, 'monthly'))->toBeNull();
});

it('defaults the upsell tracking properties to a not-shown state', function () {
    $component = Livewire::test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->get('upsellShown'))->toBeFalse()
        ->and($component->get('upsellAccepted'))->toBeFalse()
        ->and($component->get('upsellOriginalAmount'))->toBeNull();
});

/**
 * @param  array<string, mixed>  $state
 * @return array<string, mixed>
 */
function upsellTrackingParams(Campaign $campaign, array $state, string $frequency = 'monthly'): array
{
    $component = Livewire::test(DonationForm::class, ['campaign' => $campaign]);

    foreach ($state as $property => $value) {
        $component->set($property, $value);
    }

    $method = new ReflectionMethod(DonationForm::class, 'buildUpsellTrackingParams');
    $method->setAccessible(true);

    return $method->invoke($component->instance(), $frequency);
}

it('carries the upsell outcome into the tracking params', function () {
    $params = upsellTrackingParams(upsellFormCampaign(), [
        'upsellShown' => true,
        'upsellAccepted' => true,
        'upsellOriginalAmount' => 120.0,
        'amount' => '120',
    ]);

    expect($params)->toBe([
        'upsell_shown' => true,
        'upsell_accepted' => true,
        'upsell_original_amount' => 120.0,
        'upsell_offers' => [120.0, 60.0],
        'upsell_offer_taken' => 'own_amount',
    ]);
});

it('records when the donor took the lighter offer', function () {
    $params = upsellTrackingParams(upsellFormCampaign(), [
        'upsellShown' => true,
        'upsellAccepted' => true,
        'upsellOriginalAmount' => 120.0,
        'amount' => '60',
    ]);

    expect($params['upsell_offer_taken'])->toBe('lighter')
        ->and($params['upsell_offers'])->toBe([120.0, 60.0]);
});

it('records neither offer when the donor edited the amount after accepting', function () {
    $params = upsellTrackingParams(upsellFormCampaign(), [
        'upsellShown' => true,
        'upsellAccepted' => true,
        'upsellOriginalAmount' => 120.0,
        'amount' => '85',
    ]);

    expect($params['upsell_offer_taken'])->toBe('other');
});

it('refuses an acceptance that is not being submitted as monthly', function () {
    // The flags arrive from the browser, so an acceptance has to agree with
    // the frequency actually being charged.
    $params = upsellTrackingParams(upsellFormCampaign(), [
        'upsellShown' => true,
        'upsellAccepted' => true,
        'upsellOriginalAmount' => 120.0,
        'amount' => '120',
    ], frequency: 'one_time');

    expect($params['upsell_accepted'])->toBeFalse()
        ->and($params['upsell_offer_taken'])->toBeNull();
});

it('refuses an acceptance the donor was never shown', function () {
    $params = upsellTrackingParams(upsellFormCampaign(), [
        'upsellShown' => false,
        'upsellAccepted' => true,
        'upsellOriginalAmount' => 120.0,
        'amount' => '120',
    ]);

    expect($params['upsell_accepted'])->toBeFalse()
        ->and($params['upsell_original_amount'])->toBeNull()
        ->and($params['upsell_offers'])->toBeNull();
});

it('rebuilds the offers from the campaign rather than trusting the client', function () {
    // A client reporting an amount the tier does not cover gets no offers,
    // so a crafted payload cannot invent conversions.
    $params = upsellTrackingParams(upsellFormCampaign(), [
        'upsellShown' => true,
        'upsellAccepted' => true,
        'upsellOriginalAmount' => 5.0,
        'amount' => '5',
    ]);

    expect($params['upsell_offers'])->toBeNull()
        ->and($params['upsell_offer_taken'])->toBeNull();
});

it('suppresses the offer when the embed reports it already showed one', function () {
    $component = Livewire::withQueryParams(['upsell' => '1'])
        ->test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->instance()->resolveMonthlyUpsell(120.0))->toBeNull();
});

it('still offers when the embed reports it has not shown one', function () {
    $component = Livewire::withQueryParams(['upsell' => '0'])
        ->test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->instance()->resolveMonthlyUpsell(120.0))->not->toBeNull();
});

it('rejects a client attempt to overwrite the embed-suppression flag', function () {
    Livewire::test(DonationForm::class, ['campaign' => upsellFormCampaign()])
        ->set('upsellSuppressedByEmbed', true);
})->throws(CannotUpdateLockedPropertyException::class);

it('records the offer the embed already made when the modal takes over', function () {
    $component = Livewire::withQueryParams([
        'upsell' => '1',
        'upsell_accepted' => '1',
        'upsell_amount' => '120',
    ])->test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->get('upsellShown'))->toBeTrue()
        ->and($component->get('upsellAccepted'))->toBeTrue()
        ->and($component->get('upsellOriginalAmount'))->toBe(120.0);
});

it('records an offer the embed showed and the donor declined', function () {
    $component = Livewire::withQueryParams([
        'upsell' => '1',
        'upsell_accepted' => '0',
        'upsell_amount' => '120',
    ])->test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->get('upsellShown'))->toBeTrue()
        ->and($component->get('upsellAccepted'))->toBeFalse()
        ->and($component->get('upsellOriginalAmount'))->toBe(120.0);
});

it('leaves the upsell state empty when the embed made no offer', function () {
    $component = Livewire::withQueryParams(['upsell' => '0'])
        ->test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->get('upsellShown'))->toBeFalse()
        ->and($component->get('upsellOriginalAmount'))->toBeNull();
});

it('ignores a malformed original amount from the handoff', function () {
    $component = Livewire::withQueryParams([
        'upsell' => '1',
        'upsell_accepted' => '1',
        'upsell_amount' => 'not-a-number',
    ])->test(DonationForm::class, ['campaign' => upsellFormCampaign()]);

    expect($component->get('upsellShown'))->toBeTrue()
        ->and($component->get('upsellOriginalAmount'))->toBeNull();
});

it('counts an embed acceptance in the tracking params', function () {
    // The regression this covers: the modal reported upsell_accepted false for
    // every donor who accepted inside the embed.
    $component = Livewire::withQueryParams([
        'upsell' => '1',
        'upsell_accepted' => '1',
        'upsell_amount' => '120',
    ])->test(DonationForm::class, ['campaign' => upsellFormCampaign()])
        ->set('amount', '60');

    $method = new ReflectionMethod(DonationForm::class, 'buildUpsellTrackingParams');
    $method->setAccessible(true);

    expect($method->invoke($component->instance(), 'monthly'))->toBe([
        'upsell_shown' => true,
        'upsell_accepted' => true,
        'upsell_original_amount' => 120.0,
        'upsell_offers' => [120.0, 60.0],
        'upsell_offer_taken' => 'lighter',
    ]);
});
