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
            'donation_id' => (string) $donation->getKey(),
            'donor_name' => 'Aisyah Rahman',
            'donor_email' => 'aisyah@example.test',
            'donor_phone' => '+60123456789',
            'campaign_id' => (string) $campaign->getKey(),
            'organization_id' => (string) $organization->getKey(),
        ]);
});
