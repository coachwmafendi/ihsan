<?php

use App\Enums\PaymentGateway;
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

it('leads with the donor own amount and follows with a lighter tier offer', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 120.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([120.0, 60.0]);
});

it('offers the donor own amount exactly, without rounding it', function () {
    // The first button has to match the amount named in the copy, so it is the
    // donor's own figure rather than a rounded one.
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 123.0, 'myr');

    expect($offer->offers[0])->toBe(123.0)
        ->and($offer->body)->toContain('RM 123');
});

it('offers only the donor own amount when the tier yields nothing usable', function () {
    $campaign = upsellCampaign(
        ['tiers' => [['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 10]]]]],
        ['minimum_amount' => 40],
    );

    // 10% of 60 rounds to 5, below the campaign minimum of 40.
    $offer = (new MonthlyUpsellRules)->resolve($campaign, 60.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([60.0]);
});

it('matches a tier on its exact lower bound', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 50.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([50.0, 25.0]);
});

it('matches a tier on its exact upper bound', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 199.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([199.0, 100.0]);
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
        ->and($offer->offers)->toBe([5000.0, 1000.0]);
});

it('uses the first matching tier when several could apply', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => 500, 'offers' => [['type' => 'fixed', 'value' => 25]]],
            ['min' => 100, 'max' => 500, 'offers' => [['type' => 'fixed', 'value' => 90]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->offers)->toBe([120.0, 25.0]);
});

it('supports fixed offers', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 30], ['type' => 'fixed', 'value' => 45]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->offers)->toBe([120.0, 45.0]);
});

it('rounds computed offers to the nearest multiple of five', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 27]]],
        ],
    ]);

    // 27% of 130 is 35.1, which rounds to 35.
    $offer = (new MonthlyUpsellRules)->resolve($campaign, 130.0, 'myr');

    expect($offer->offers)->toBe([130.0, 35.0]);
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

    expect($offer->offers)->toBe([60.0, 30.0]);
});

it('drops offers that reach or exceed the one-time amount', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 50], ['type' => 'percent', 'value' => 100]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 100.0, 'myr');

    expect($offer->offers)->toBe([100.0, 50.0]);
});

it('collapses offers that are identical after rounding', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 33], ['type' => 'percent', 'value' => 34]]],
        ],
    ]);

    // 33% and 34% of 120 are 39.6 and 40.8, both rounding to 40.
    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->offers)->toBe([120.0, 40.0]);
});

it('picks the largest tier offer as the lighter alternative', function () {
    // Two buttons keep the decision simple, so only the highest configured
    // offer below the donor's amount is shown alongside it.
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 40], ['type' => 'fixed', 'value' => 60]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->offers)->toBe([120.0, 60.0]);
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
        ->and($offer->body)->toBe('Would you consider making your RM 120 contribution a monthly donation? Your ongoing support helps us continue our work and make a lasting impact.')
        ->and($offer->declineLabel)->toBe('No, keep my one-time RM 120 gift')
        ->and($offer->cooldownDays)->toBe(30);
});

it('uses the campaign copy overrides when present', function () {
    $campaign = upsellCampaign([
        'heading' => 'Jadi penyokong bulanan',
        'body' => 'Tukar sumbangan :amount anda kepada bulanan?',
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->heading)->toBe('Jadi penyokong bulanan')
        ->and($offer->body)->toBe('Tukar sumbangan RM 120 anda kepada bulanan?');
});

it('falls back to the default heading when the override is an empty string', function () {
    $campaign = upsellCampaign(['heading' => '']);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->heading)->toBe('Become a monthly supporter');
});

it('skips an offer with an unrecognised type', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'Fixed', 'value' => 30]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    // The type is skipped, leaving only the donor's own amount to offer.
    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([120.0]);
});

it('returns null for a chip campaign whose organization only enables fpx', function () {
    $organization = Organization::factory()->create([
        'settings' => ['chip_payment_methods' => ['fpx']],
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'allow_recurring' => true,
        'payment_gateway' => PaymentGateway::Chip,
        'config' => ['monthly_upsell' => [
            'enabled' => true,
            'cooldown_days' => 30,
            'tiers' => [
                ['min' => 50, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 30]]],
            ],
        ]],
    ])->load('organization');

    expect((new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr'))->toBeNull();
});

it('returns an offer for a chip campaign whose organization enables card and fpx', function () {
    $organization = Organization::factory()->create([
        'settings' => ['chip_payment_methods' => ['card', 'fpx']],
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'allow_recurring' => true,
        'payment_gateway' => PaymentGateway::Chip,
        'config' => ['monthly_upsell' => [
            'enabled' => true,
            'cooldown_days' => 30,
            'tiers' => [
                ['min' => 50, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 30]]],
            ],
        ]],
    ])->load('organization');

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([120.0, 30.0]);
});

it('does not let a malformed tier shadow a valid tier that follows it', function () {
    $campaign = upsellCampaign([
        'tiers' => [
            ['offers' => [['type' => 'fixed', 'value' => 999]]],
            ['min' => 50, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 30]]],
        ],
    ]);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer)->not->toBeNull()
        ->and($offer->offers)->toBe([120.0, 30.0]);
});

it('accepts a well-formed tier config', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => 199, 'offers' => [['type' => 'percent', 'value' => 33]]],
        ['min' => 200, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 100]]],
    ]);

    expect($errors)->toBe([]);
});

it('rejects a tier with no minimum', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 0, 'max' => 199, 'offers' => [['type' => 'percent', 'value' => 33]]],
    ]);

    expect($errors)->toContain('Tier 1: the minimum must be greater than zero.');
});

it('rejects a tier whose maximum is not above its minimum', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 200, 'max' => 100, 'offers' => [['type' => 'percent', 'value' => 33]]],
    ]);

    expect($errors)->toContain('Tier 1: the maximum must be greater than the minimum.');
});

it('rejects overlapping tiers', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => 199, 'offers' => [['type' => 'percent', 'value' => 33]]],
        ['min' => 150, 'max' => 400, 'offers' => [['type' => 'percent', 'value' => 20]]],
    ]);

    expect($errors)->toContain('Tier 2 overlaps tier 1.');
});

it('rejects a percent offer of zero or one hundred', function () {
    $rules = new MonthlyUpsellRules;

    expect($rules->validateConfig([
        ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 0]]],
    ]))->toContain('Tier 1, offer 1: a percentage must be between 1 and 99.');

    expect($rules->validateConfig([
        ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 100]]],
    ]))->toContain('Tier 1, offer 1: a percentage must be between 1 and 99.');
});

it('rejects a fixed offer of zero', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => null, 'offers' => [['type' => 'fixed', 'value' => 0]]],
    ]);

    expect($errors)->toContain('Tier 1, offer 1: the amount must be greater than zero.');
});

it('rejects a config with no tiers at all', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([]);

    expect($errors)->toContain('Add at least one tier.');
});

it('rejects a tier with no offers', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => null, 'offers' => []],
    ]);

    expect($errors)->toContain('Tier 1: add at least one offer.');
});

it('rejects a tier with more than the maximum offers', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => null, 'offers' => [
            ['type' => 'percent', 'value' => 10],
            ['type' => 'percent', 'value' => 20],
            ['type' => 'percent', 'value' => 30],
        ]],
    ]);

    expect($errors)->toContain('Tier 1: add at most 2 offers.');
});

it('rejects more than six tiers', function () {
    $tier = ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 33]]];

    $errors = (new MonthlyUpsellRules)->validateConfig(array_fill(0, 7, $tier));

    expect($errors)->toContain('At most 6 tiers are allowed.');
});

it('rejects an offer whose type is not percent or fixed', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => null, 'offers' => [['type' => 'Fixed', 'value' => 30]]],
    ]);

    expect($errors)->toContain('Tier 1, offer 1: choose either a percentage or a fixed amount.');
});

it('validates an offer with no type as a percentage', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => null, 'offers' => [['value' => 33]]],
    ]);

    expect($errors)->toBe([]);
});

it('rejects a non-array tier instead of throwing', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        'garbage',
        ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => 33]]],
    ]);

    expect($errors)->toContain('Tier 1 is not configured correctly.');
});

it('treats an empty string max as unbounded', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => '', 'offers' => [['type' => 'percent', 'value' => 33]]],
        ['min' => 200, 'max' => '', 'offers' => [['type' => 'percent', 'value' => 20]]],
    ]);

    expect($errors)
        ->not->toContain('Tier 1: the maximum must be greater than the minimum.')
        ->and($errors)->toContain('Tier 2 overlaps tier 1.');
});

it('rejects a non-numeric minimum', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => '50abc', 'max' => null, 'offers' => [['type' => 'percent', 'value' => 33]]],
    ]);

    expect($errors)->toContain('Tier 1: the minimum must be a number.');
});

it('rejects a non-numeric maximum', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => '200abc', 'offers' => [['type' => 'percent', 'value' => 33]]],
    ]);

    expect($errors)->toContain('Tier 1: the maximum must be a number.');
});

it('rejects a non-numeric offer value', function () {
    $errors = (new MonthlyUpsellRules)->validateConfig([
        ['min' => 50, 'max' => null, 'offers' => [['type' => 'percent', 'value' => '33abc']]],
    ]);

    expect($errors)->toContain('Tier 1, offer 1: the value must be a number.');
});

it('previews what a tier would offer for a sample amount', function () {
    $tier = ['min' => 50, 'max' => 199, 'offers' => [
        ['type' => 'percent', 'value' => 33],
        ['type' => 'percent', 'value' => 50],
    ]];

    $rules = new MonthlyUpsellRules;

    expect($rules->previewTier($tier, 120.0))->toBe([120.0, 60.0])
        ->and($rules->previewTier($tier, 50.0))->toBe([50.0, 25.0]);
});

it('samples a bounded tier at its ends and midpoint', function () {
    $amounts = (new MonthlyUpsellRules)->previewAmountsFor(['min' => 50, 'max' => 200]);

    expect($amounts)->toBe([50.0, 125.0, 200.0]);
});

it('steps up from the minimum when previewing an open-ended tier', function () {
    $amounts = (new MonthlyUpsellRules)->previewAmountsFor(['min' => 100, 'max' => null]);

    expect($amounts)->toBe([100.0, 200.0, 500.0]);
});

it('has nothing to preview when the tier maximum is not a number', function () {
    $amounts = (new MonthlyUpsellRules)->previewAmountsFor(['min' => 50, 'max' => 'lots']);

    expect($amounts)->toBe([]);
});

it('has nothing to preview when the tier minimum is not set', function () {
    expect((new MonthlyUpsellRules)->previewAmountsFor(['min' => 0, 'max' => 100]))->toBe([]);
});

it('splits the message so the form can bold the amount without markup', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 120.0, 'myr');

    expect($offer->amountLabel)->toBe('RM 120')
        ->and($offer->bodySegments)->toBe([
            'Would you consider making your ',
            ' contribution a monthly donation? Your ongoing support helps us continue our work and make a lasting impact.',
        ])
        ->and(implode($offer->amountLabel, $offer->bodySegments))->toBe($offer->body);
});

it('keeps the cents on an amount that has them', function () {
    $offer = (new MonthlyUpsellRules)->resolve(upsellCampaign(), 120.5, 'myr');

    expect($offer->amountLabel)->toBe('RM 120.50')
        ->and($offer->declineLabel)->toBe('No, keep my one-time RM 120.50 gift');
});

it('leaves a message without the placeholder as a single segment', function () {
    $campaign = upsellCampaign(['body' => 'Sokong kami setiap bulan.']);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->bodySegments)->toBe(['Sokong kami setiap bulan.'])
        ->and($offer->body)->toBe('Sokong kami setiap bulan.');
});

it('uses the campaign decline label override when present', function () {
    $campaign = upsellCampaign(['decline_label' => 'Tidak, kekalkan derma :amount sekali sahaja']);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->declineLabel)->toBe('Tidak, kekalkan derma RM 120 sekali sahaja');
});

it('falls back to the default decline label when the override is blank', function () {
    $campaign = upsellCampaign(['decline_label' => '   ']);

    $offer = (new MonthlyUpsellRules)->resolve($campaign, 120.0, 'myr');

    expect($offer->declineLabel)->toBe('No, keep my one-time RM 120 gift');
});

it('has nothing to preview while the tier range is still invalid', function () {
    $rules = new MonthlyUpsellRules;

    // Both shapes are rejected by validateConfig, so previewing them would
    // advertise amounts the tier could never match.
    expect($rules->previewAmountsFor(['min' => 200, 'max' => 100]))->toBe([])
        ->and($rules->previewAmountsFor(['min' => 50, 'max' => 50]))->toBe([]);
});

it('still previews an open-ended tier by stepping up from the minimum', function () {
    $rules = new MonthlyUpsellRules;

    expect($rules->previewAmountsFor(['min' => 100, 'max' => null]))->toBe([100.0, 200.0, 500.0])
        ->and($rules->previewAmountsFor(['min' => 100, 'max' => '']))->toBe([100.0, 200.0, 500.0]);
});

it('reports an offer that the higher one always beats', function () {
    $tier = ['min' => 50, 'max' => 199, 'offers' => [
        ['type' => 'percent', 'value' => 33],
        ['type' => 'percent', 'value' => 50],
    ]];

    $rules = new MonthlyUpsellRules;

    expect($rules->unusedOfferLabels($tier, 'MYR'))->toBe(['33%']);
});

it('reports nothing when a tier offers a single value', function () {
    $tier = ['min' => 50, 'max' => 199, 'offers' => [
        ['type' => 'percent', 'value' => 50],
    ]];

    expect((new MonthlyUpsellRules)->unusedOfferLabels($tier, 'MYR'))->toBe([]);
});

it('reports nothing when both offers win at some amount', function () {
    $tier = ['min' => 50, 'max' => 400, 'offers' => [
        ['type' => 'fixed', 'value' => 30],
        ['type' => 'percent', 'value' => 10],
    ]];

    expect((new MonthlyUpsellRules)->unusedOfferLabels($tier, 'MYR'))->toBe([]);
});

it('handles a tier whose minimum is not a whole number', function () {
    $tier = ['min' => 12.5, 'max' => 99, 'offers' => [
        ['type' => 'fixed', 'value' => 10],
        ['type' => 'percent', 'value' => 20],
    ]];

    // A fractional sample amount must not be used as an array key: PHP would
    // truncate 12.5 to 12 and look up the wrong row.
    expect((new MonthlyUpsellRules)->unusedOfferLabels($tier, 'MYR'))->toBe([]);
});

it('counts an offer the campaign minimum filters out as unused', function () {
    $tier = ['min' => 50, 'max' => 199, 'offers' => [
        ['type' => 'fixed', 'value' => 40],
        ['type' => 'fixed', 'value' => 45],
    ]];

    $rules = new MonthlyUpsellRules;

    expect($rules->unusedOfferLabels($tier, 'MYR', 60.0))
        ->toHaveCount(2);
});
