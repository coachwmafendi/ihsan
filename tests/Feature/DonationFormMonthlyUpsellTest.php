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

it('carries the upsell outcome into the tracking params', function () {
    $component = Livewire::test(DonationForm::class, ['campaign' => upsellFormCampaign()])
        ->set('upsellShown', true)
        ->set('upsellAccepted', true)
        ->set('upsellOriginalAmount', 120.0);

    $method = new ReflectionMethod(DonationForm::class, 'buildUpsellTrackingParams');
    $method->setAccessible(true);

    expect($method->invoke($component->instance()))->toBe([
        'upsell_shown' => true,
        'upsell_accepted' => true,
        'upsell_original_amount' => 120.0,
    ]);
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
