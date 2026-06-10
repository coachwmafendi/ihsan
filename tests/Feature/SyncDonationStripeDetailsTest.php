<?php

use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

beforeEach(function () {
    config(['services.stripe.secret' => 'sk_test_fake']);
});

it('saves donor payment method after syncing donation stripe details', function () {
    $stripeClient = new class implements ClientInterface
    {
        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $response = match (true) {
                str_ends_with($absUrl, '/v1/payment_methods/pm_test_card') => [
                    'id' => 'pm_test_card',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                        'exp_month' => 12,
                        'exp_year' => 2030,
                        'country' => 'MY',
                    ],
                    'billing_details' => [
                        'address' => [
                            'line1' => '123 Main St',
                            'city' => 'Kuala Lumpur',
                            'country' => 'MY',
                        ],
                    ],
                ],
                str_contains($absUrl, '/v1/payment_intents/') => [
                    'id' => 'pi_test_123',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'payment_method' => 'pm_test_card',
                    'latest_charge' => [
                        'id' => 'ch_test_123',
                        'object' => 'charge',
                        'balance_transaction' => [
                            'id' => 'bt_test_123',
                            'object' => 'balance_transaction',
                            'fee' => 50,
                            'fee_details' => [
                                [
                                    'type' => 'stripe_fee',
                                    'amount' => 30,
                                    'currency' => 'myr',
                                    'description' => 'Stripe processing fee',
                                ],
                                [
                                    'type' => 'application_fee',
                                    'amount' => 20,
                                    'currency' => 'myr',
                                    'description' => 'Platform fee',
                                ],
                            ],
                            'exchange_rate' => null,
                        ],
                        'billing_details' => [
                            'name' => 'Ahmad Ali',
                            'address' => [
                                'line1' => '123 Main St',
                                'city' => 'Kuala Lumpur',
                                'country' => 'MY',
                            ],
                        ],
                    ],
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.test',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 100,
        'currency' => 'myr',
        'stripe_payment_intent_id' => 'pi_test_123',
    ]);

    try {
        app(SyncDonationStripeDetails::class)->sync($donation);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $this->assertDatabaseHas('donor_payment_methods', [
        'donor_id' => $donor->id,
        'stripe_payment_method_id' => 'pm_test_card',
        'brand' => 'Visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2030,
        'country' => 'MY',
        'is_default' => true,
    ]);
});

it('updates existing donor payment method instead of duplicating', function () {
    $stripeClient = new class implements ClientInterface
    {
        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $response = match (true) {
                str_ends_with($absUrl, '/v1/payment_methods/pm_existing_card') => [
                    'id' => 'pm_existing_card',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'mastercard',
                        'last4' => '5555',
                        'exp_month' => 6,
                        'exp_year' => 2028,
                        'country' => 'US',
                    ],
                    'billing_details' => [
                        'address' => null,
                    ],
                ],
                str_contains($absUrl, '/v1/payment_intents/') => [
                    'id' => 'pi_test_456',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'payment_method' => 'pm_existing_card',
                    'latest_charge' => [
                        'id' => 'ch_test_456',
                        'object' => 'charge',
                        'balance_transaction' => [
                            'id' => 'bt_test_456',
                            'object' => 'balance_transaction',
                            'fee' => 50,
                            'fee_details' => [
                                [
                                    'type' => 'stripe_fee',
                                    'amount' => 50,
                                    'currency' => 'myr',
                                    'description' => 'Stripe processing fee',
                                ],
                            ],
                            'exchange_rate' => null,
                        ],
                        'billing_details' => [
                            'name' => null,
                            'address' => null,
                        ],
                    ],
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'name' => 'Siti Nur',
        'email' => 'siti@example.test',
    ]);

    DonorPaymentMethod::create([
        'donor_id' => $donor->id,
        'stripe_payment_method_id' => 'pm_existing_card',
        'brand' => 'Visa',
        'last4' => '1111',
        'exp_month' => 1,
        'exp_year' => 2025,
        'country' => 'MY',
        'is_default' => false,
    ]);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
        'stripe_payment_intent_id' => 'pi_test_456',
    ]);

    try {
        app(SyncDonationStripeDetails::class)->sync($donation);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $this->assertDatabaseHas('donor_payment_methods', [
        'donor_id' => $donor->id,
        'stripe_payment_method_id' => 'pm_existing_card',
        'brand' => 'Mastercard',
        'last4' => '5555',
        'exp_month' => 6,
        'exp_year' => 2028,
        'country' => 'US',
        'is_default' => true,
    ]);

    expect(DonorPaymentMethod::where('donor_id', $donor->id)->count())->toBe(1);
});

it('skips donor payment method sync when payment method is missing', function () {
    $stripeClient = new class implements ClientInterface
    {
        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $response = match (true) {
                str_contains($absUrl, '/v1/payment_intents/') => [
                    'id' => 'pi_test_789',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'payment_method' => null,
                    'latest_charge' => [
                        'id' => 'ch_test_789',
                        'object' => 'charge',
                        'balance_transaction' => [
                            'id' => 'bt_test_789',
                            'object' => 'balance_transaction',
                            'fee' => 0,
                            'fee_details' => [],
                            'exchange_rate' => null,
                        ],
                        'billing_details' => [
                            'name' => null,
                            'address' => null,
                        ],
                    ],
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
        'stripe_payment_intent_id' => 'pi_test_789',
    ]);

    try {
        app(SyncDonationStripeDetails::class)->sync($donation);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    expect(DonorPaymentMethod::count())->toBe(0);
});
