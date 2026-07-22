<?php

use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use App\Services\GeoLocation\MaxMindGeoIp;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function geoCurrencyElement(array $campaignConfig = [], array $orgSettings = []): Element
{
    $organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr', 'usd', 'sgd'], ...$orgSettings],
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'config' => ['currency_autodetect' => true, ...$campaignConfig],
    ]);

    return Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
        'config' => [
            'suggested_amounts' => [200, 100, 50, 30, 10, 5],
            'allow_monthly' => true,
        ],
    ]);
}

function fakeCountry(?string $code): void
{
    Http::fake();

    app()->instance(MaxMindGeoIp::class, Mockery::mock(MaxMindGeoIp::class, function ($mock) use ($code) {
        $mock->shouldReceive('country')->andReturn($code);
        $mock->shouldReceive('lookup')->andReturnNull();
    }));
}

it('autodetects the supporter currency from their country', function () {
    fakeCountry('SG');

    $element = geoCurrencyElement();

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'sgd');
});

it('falls back to usd for countries without a local currency', function () {
    fakeCountry('GB');

    $element = geoCurrencyElement();

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'usd');
});

it('keeps myr for malaysian supporters', function () {
    fakeCountry('MY');

    $element = geoCurrencyElement();

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'myr');
});

it('does nothing when autodetect is disabled', function () {
    fakeCountry('SG');

    $element = geoCurrencyElement(campaignConfig: ['currency_autodetect' => false]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'myr');
});

it('ignores a detected currency the organization does not accept', function () {
    fakeCountry('SG');

    $element = geoCurrencyElement(orgSettings: ['accepted_currencies' => ['myr']]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'myr');
});

it('lets an explicit currency query param win over autodetect', function () {
    fakeCountry('SG');

    $element = geoCurrencyElement();

    Livewire::withQueryParams(['currency' => 'usd'])
        ->test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'usd');
});
