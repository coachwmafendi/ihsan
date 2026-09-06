<?php

declare(strict_types=1);

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

uses(RefreshDatabase::class);

/**
 * The donor's fee cover exists to pay the Stripe and platform fees. Taking a
 * cut of it too means the organization receives less than the donation the
 * donor intended, while the checkout promises "100% of your donation".
 *
 * @param  array<int, array<string, mixed>>  $requests
 */
function fakeStripeIntentClient(array &$requests): ClientInterface
{
    return new class($requests) implements ClientInterface
    {
        /**
         * @param  array<int, array<string, mixed>>  $requests
         */
        public function __construct(private array &$requests) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
        {
            $this->requests[] = ['url' => $absUrl, 'params' => $params];

            $response = [
                'id' => 'pi_fee_base',
                'object' => 'payment_intent',
                'status' => 'requires_payment_method',
                'client_secret' => 'pi_fee_base_secret',
                'amount' => $params['amount'] ?? 0,
            ];

            return [json_encode($response), 200, []];
        }
    };
}

afterEach(function () {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

it('charges the platform fee on the donation, not on the donor fee cover', function () {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_fee_base',
        'stripe_onboarded' => true,
        'stripe_enabled' => true,
        'fee_collection_method' => 'upfront',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Pending,
        'gross_amount' => 100.00,
        'donor_fee_covered' => 6.50,
        'currency' => 'myr',
    ]);

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeIntentClient($requests));

    app(CreatePaymentIntent::class)->create($donation);

    $intentRequest = collect($requests)
        ->first(fn (array $request): bool => str_contains($request['url'], '/v1/payment_intents'));

    expect($intentRequest)->not->toBeNull()
        // The donor is charged the full RM106.50...
        ->and($intentRequest['params']['amount'])->toBe(10650)
        // ...but our 2.5% applies to the RM100 donation only.
        ->and($intentRequest['params']['application_fee_amount'])->toBe(250);
});

it('charges the platform fee on the full amount when the donor covers nothing', function () {
    config(['services.stripe.processing_fee_percent' => 2.5]);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_fee_base_plain',
        'stripe_onboarded' => true,
        'stripe_enabled' => true,
        'fee_collection_method' => 'upfront',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Pending,
        'gross_amount' => 100.00,
        'donor_fee_covered' => 0,
        'currency' => 'myr',
    ]);

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeIntentClient($requests));

    app(CreatePaymentIntent::class)->create($donation);

    $intentRequest = collect($requests)
        ->first(fn (array $request): bool => str_contains($request['url'], '/v1/payment_intents'));

    expect($intentRequest['params']['amount'])->toBe(10000)
        ->and($intentRequest['params']['application_fee_amount'])->toBe(250);
});
