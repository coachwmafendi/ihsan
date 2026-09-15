<?php

declare(strict_types=1);

use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Stripe;

/**
 * The counter behind the public "Raised" figure, and the rate that arrives late.
 *
 * A campaign's collected_amount is incremented when a donation succeeds, using
 * base_amount where there is one and the gross otherwise. For a donation in
 * another currency the base amount comes from Stripe's balance transaction,
 * which frequently does not exist yet at that moment - so the counter took the
 * foreign number as if it were ringgit. SGD 50 was counted as RM 50 rather
 * than RM 160.63.
 *
 * The sync that runs two minutes later fills base_amount in correctly, and
 * nothing went back to the counter. Every admin screen sums the donations
 * live and was right; the campaign page reads the counter and was not.
 */
beforeEach(function () {
    config(['services.stripe.secret' => 'sk_test_fake']);
    Stripe::setApiKey('sk_test_fake');
});

/**
 * A Stripe client whose balance transaction carries the given exchange rate.
 */
function stripeClientWithExchangeRate(?float $rate): ClientInterface
{
    return new class($rate) implements ClientInterface
    {
        public function __construct(private ?float $rate) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $response = match (true) {
                str_contains($absUrl, '/v1/payment_methods/') => [
                    'id' => 'pm_test_card',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => ['brand' => 'visa', 'last4' => '4242', 'country' => 'SG'],
                    'billing_details' => ['address' => ['country' => 'SG']],
                ],
                str_contains($absUrl, '/v1/payment_intents/') => [
                    'id' => 'pi_test_sgd',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'payment_method' => 'pm_test_card',
                    'latest_charge' => [
                        'id' => 'ch_test_sgd',
                        'object' => 'charge',
                        'balance_transaction' => [
                            'id' => 'bt_test_sgd',
                            'object' => 'balance_transaction',
                            'fee' => 0,
                            'fee_details' => [],
                            'exchange_rate' => $this->rate,
                        ],
                        'billing_details' => ['address' => ['country' => 'SG']],
                    ],
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };
}

/**
 * @return array{0: Campaign, 1: Donation}
 */
function sgdDonationCountedAtItsGross(float $collectedBefore = 0.0): array
{
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'collected_amount' => $collectedBefore + 50,
    ]);
    $donor = Donor::factory()->create();

    // Succeeded, counted, and still carrying no base amount: the state a
    // foreign donation is left in when the balance transaction lags.
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'base_amount' => null,
        'currency' => 'sgd',
        'status' => DonationStatus::Succeeded,
        'stripe_payment_intent_id' => 'pi_test_sgd',
    ]);

    return [$campaign, $donation];
}

it('corrects the campaign total when the exchange rate arrives late', function () {
    [$campaign, $donation] = sgdDonationCountedAtItsGross();

    ApiRequestor::setHttpClient(stripeClientWithExchangeRate(3.2126));

    try {
        app(SyncDonationStripeDetails::class)->sync($donation);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    expect((float) $donation->fresh()->base_amount)->toBe(160.63);

    // Counted as 50, worth 160.63: the counter owes the difference.
    expect((float) $campaign->fresh()->collected_amount)->toBe(160.63);
});

it('leaves the campaign total alone when the rate was there all along', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create(['collected_amount' => 160.63]);
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'base_amount' => 160.63,
        'currency' => 'sgd',
        'status' => DonationStatus::Succeeded,
        'stripe_payment_intent_id' => 'pi_test_sgd',
    ]);

    ApiRequestor::setHttpClient(stripeClientWithExchangeRate(3.2126));

    try {
        app(SyncDonationStripeDetails::class)->sync($donation);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    expect((float) $campaign->fresh()->collected_amount)->toBe(160.63);
});

it('does not count a donation that has not succeeded yet', function () {
    // Here the sync runs before the donation is marked succeeded, and the
    // finalising transaction is what adds it to the counter. Adjusting here
    // too would count the same donation twice.
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create(['collected_amount' => 0]);
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'base_amount' => null,
        'currency' => 'sgd',
        'status' => DonationStatus::Pending,
        'stripe_payment_intent_id' => 'pi_test_sgd',
    ]);

    ApiRequestor::setHttpClient(stripeClientWithExchangeRate(3.2126));

    try {
        app(SyncDonationStripeDetails::class)->sync($donation);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    expect((float) $campaign->fresh()->collected_amount)->toBe(0.0);
});

it('reports a drifted counter without touching it on a dry run', function () {
    $campaign = Campaign::factory()->for(Organization::factory())->create(['collected_amount' => 50]);
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'base_amount' => 160.63,
        'currency' => 'sgd',
        'status' => DonationStatus::Succeeded,
    ]);

    $this->artisan('campaign:recalculate-collected --dry-run')
        ->expectsOutputToContain('would update')
        ->assertSuccessful();

    expect((float) $campaign->fresh()->collected_amount)->toBe(50.0);
});

it('puts a drifted counter back in step with its donations', function () {
    $campaign = Campaign::factory()->for(Organization::factory())->create(['collected_amount' => 50]);
    $donor = Donor::factory()->create();

    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'base_amount' => 160.63,
        'currency' => 'sgd',
        'status' => DonationStatus::Succeeded,
    ]);

    // A refunded donation is not in the counter and must not be added back.
    Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 20,
        'base_amount' => 20,
        'currency' => 'myr',
        'status' => DonationStatus::Refunded,
    ]);

    $this->artisan('campaign:recalculate-collected')->assertSuccessful();

    expect((float) $campaign->fresh()->collected_amount)->toBe(160.63);
});
