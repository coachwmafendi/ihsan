<?php

use App\Actions\Stripe\CreatePaymentIntent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

it('sends donor contact details to stripe payment intent records', function () {
    config(['services.stripe.secret' => 'sk_test_fake']);

    $stripeClient = new class implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, params: array<string, mixed>}> */
        public array $requests = [];

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'params' => $params,
            ];

            return [
                json_encode([
                    'id' => 'pi_test_contact_details',
                    'object' => 'payment_intent',
                    'client_secret' => 'pi_test_contact_details_secret',
                    'status' => 'requires_payment_method',
                ]),
                200,
                [],
            ];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->withoutStripe()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Wakaf Pembangunan',
    ]);
    $donor = Donor::factory()->create([
        'name' => 'Aisyah Rahman',
        'email' => 'aisyah@example.test',
        'phone' => '+60123456789',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 30,
        'currency' => 'myr',
    ]);

    try {
        app(CreatePaymentIntent::class)->create($donation);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    expect($stripeClient->requests)->toHaveCount(1);

    $params = $stripeClient->requests[0]['params'];

    expect($params)
        ->toMatchArray([
            'receipt_email' => 'aisyah@example.test',
            'description' => 'Wakaf Pembangunan',
        ])
        ->and($params['metadata'])->toMatchArray([
            'ihsan_donation_id' => (string) $donation->getKey(),
            'ihsan_donor_name' => 'Aisyah Rahman',
            'ihsan_donor_email' => 'aisyah@example.test',
            'ihsan_donor_phone' => '+60123456789',
            'ihsan_campaign_id' => (string) $campaign->getKey(),
            'ihsan_organization_id' => (string) $organization->getKey(),
            'ihsan_source' => 'checkout_modal',
            'ihsan_environment' => 'testing',
        ]);
});

it('creates connected account customers for connected organization payments', function () {
    config(['services.stripe.secret' => 'sk_test_fake']);

    $stripeClient = new class implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>}> */
        public array $requests = [];

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'headers' => $headers,
                'params' => $params,
            ];

            $response = match (true) {
                str_ends_with($absUrl, '/v1/account') => [
                    'id' => 'acct_platform',
                    'object' => 'account',
                ],
                str_ends_with($absUrl, '/v1/customers') => [
                    'id' => 'cus_connected_donor',
                    'object' => 'customer',
                    'email' => $params['email'] ?? null,
                    'name' => $params['name'] ?? null,
                    'phone' => $params['phone'] ?? null,
                ],
                str_ends_with($absUrl, '/v1/payment_intents') => [
                    'id' => 'pi_connected_customer',
                    'object' => 'payment_intent',
                    'client_secret' => 'pi_connected_customer_secret',
                    'status' => 'requires_payment_method',
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_connected_test',
        'stripe_onboarded' => true,
        'fee_collection_method' => 'invoice',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Connected Campaign',
    ]);
    $donor = Donor::factory()->create([
        'first_name' => 'Connected',
        'last_name' => 'Donor',
        'email' => 'connected@example.test',
        'phone' => '+60129876543',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 50,
        'currency' => 'myr',
    ]);

    try {
        app(CreatePaymentIntent::class)->create($donation);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $customerRequest = collect($stripeClient->requests)
        ->first(fn (array $request): bool => str_ends_with($request['url'], '/v1/customers'));
    $paymentIntentRequest = collect($stripeClient->requests)
        ->first(fn (array $request): bool => str_ends_with($request['url'], '/v1/payment_intents'));

    expect($customerRequest)->not->toBeNull()
        ->and($customerRequest['headers'])->toContain('Stripe-Account: acct_connected_test')
        ->and($customerRequest['params'])->toMatchArray([
            'email' => 'connected@example.test',
            'name' => 'Connected Donor',
            'phone' => '+60129876543',
        ])
        ->and($paymentIntentRequest)->not->toBeNull()
        ->and($paymentIntentRequest['headers'])->toContain('Stripe-Account: acct_connected_test')
        ->and($paymentIntentRequest['params'])->toMatchArray([
            'customer' => 'cus_connected_donor',
        ]);

    expect($paymentIntentRequest['params'])->not->toHaveKey('application_fee_amount')
        ->and($paymentIntentRequest['params'])->not->toHaveKey('transfer_data');

    $donor->refresh();
    expect($donor->stripe_customer_id)->toBe('cus_connected_donor');
});
