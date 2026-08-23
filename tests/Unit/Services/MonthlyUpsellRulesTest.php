<?php

use App\Models\Campaign;
use App\Models\Organization;
use App\Services\MonthlyUpsellRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $upsell
 * @param  array<string, mixed>  $attributes
 */
function upsellCampaign(array $upsell = [], array $attributes = []): Campaign
{
    $default = [
        'enabled' => true,
        'cooldown_days' => 30,
        'tiers' => [
            [
                'min' => 50,
                'max' => 199,
                'offers' => [
                    ['type' => 'percent', 'value' => 33],
                    ['type' => 'percent', 'value' => 50],
                ],
            ],
        ],
    ];

    $campaign = Campaign::factory()
        ->for(Organization::factory())
        ->create([
            'allow_recurring' => true,
            'config' => ['monthly_upsell' => array_replace($default, $upsell)],
            ...$attributes,
        ]);

    return $campaign->load('organization');
}

it('offers two amounts derived from the matching tier', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 120.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([40.0, 60.0]);
});

it('matches a tier on its exact lower bound', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 50.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([15.0, 25.0]);
});

it('matches a tier on its exact upper bound', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 199.0, 'myr');

    expect($offer)->not->toBeNull();
});

it('returns null when no tier matches the amount', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 20.0, 'myr');

    expect($offer)->toBeNull();
});

it('treats a null max as an open-ended tier', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 200, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 20]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 5000.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([1000.0]);
});

it('uses the first matching tier when several could apply', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => 500, 'offers' => [['type' => 'fixed', 'value' => 25]]],
            ['min' => 100, 'max' => 500, 'offers' => [['type' => 'fixed', 'value' => 90]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->offers)->toBe([25.0]);
});

it('supports fixed offers', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 30], ['type' => 'fixed', 'value' => 45]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->offers)->toBe([30.0, 45.0]);
});

it('rounds computed offers to the nearest multiple of five', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 27]]],
        ],
    ]);

    // 27% of 130 is 35.1, which rounds to 35.
    $offer = (new MonthlyUpsellRules)->resolve($campaign, 130.0, 'myr');

    expect($offer->offers)->toBe([35.0]);
});

it('drops offers below the campaign minimum amount', function () {
    $campaign = upsellCampaign(
        [
            'tiers' => [
                ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 10], ['type' => 'percent', 'value' => 50]]],
            ],
        ],
        ['minimum_amount' => 20],
    );

    // 10% of 60 rounds to 5, below the minimum of 20. 50% is 30 and survives.
    $offer = (new MonthlyUpsellRules)->resolve($campaign, 60.0, 'myr');

    expect($offer->offers)->toBe([30.0]);
});

it('returns null when every offer falls below the minimum', function () {
    $campaign = upsellCampaign(
        [
            'tiers' => [
                ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 10]]],
            ],
        ],
        ['minimum_amount' => 40],
    );

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 60.0, 'myr');

    expect($offer)->toBeNull();
});

it('drops offers that reach or exceed the one-time amount', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 50], ['type' => 'percent', 'value' => 100]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 100.0, 'myr');

    expect($offer->offers)->toBe([50.0]);
});

it('collapses offers that are identical after rounding', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 33], ['type' => 'percent', 'value' => 34]]],
        ],
    ]);

    // 33% and 34% of 120 are 39.6 and 40.8, both rounding to 40.
    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->offers)->toBe([40.0]);
});

it('sorts offers ascending regardless of config order', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 60], ['type' => 'fixed', 'value' => 40]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->offers)->toBe([40.0, 60.0]);
});

it('returns null when the upsell is disabled', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(['enabled' => false]), 120.0, 'myr');

    expect($offer)->toBeNull();
});

it('returns null when the campaign has no upsell config at all', function () {
    $campaign = Campaign::factory()->for(Organization::factory())->create([
        'allow_recurring' => true,
        'config' => [],
    ])->load('organization');

    expect((new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr'))->toBeNull();
});

it('returns null when the campaign does not allow recurring donations', function () {
    $campaign = upsellCampaign([], ['allow_recurring' => false]);

    expect((new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr'))->toBeNull();
});

it('returns null when a malformed tier would otherwise throw', function () {
    $campaign = upsellCampaign(['tiers' => 'not-an-array']);

    expect((new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr'))->toBeNull();
});

it('builds copy with the one-time amount substituted', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 120.0, 'myr');

    expect($offer->heading)->toBe('Become a monthly supporter')
        ->and($offer->body)->toContain('RM 120.00')
        ->and($offer->declineLabel)->toBe('No, keep my one-time RM 120.00 gift')
        ->and($offer->cooldownDays)->toBe(30);
});

it('uses the campaign copy overrides when present', function () {
    $campaign = upsellCampaign([
        'heading' => 'Jadi penyokong bulanan',
        'body' => 'Tukar sumbangan :amount anda kepada bulanan?',
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->heading)->toBe('Jadi penyokong bulanan')
        ->and($offer->body)->toBe('Tukar sumbangan RM 120.00 anda kepada bulanan?');
});
