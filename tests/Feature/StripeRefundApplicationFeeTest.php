<?php

declare(strict_types=1);

use App\Actions\Stripe\RefundDonation;
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
 * @param  array<int, array<string, mixed>>  $requests
 */
function fakeStripeRefundClient(array &$requests): ClientInterface
{
    return new class($requests) implements ClientInterface
    {
        /**
         * @param  array<int, array<string, mixed>>  $requests
         */
        public function __construct(private array &$requests) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
        {
            $this->requests[] = ['method' => $method, 'url' => $absUrl, 'params' => $params];

            return [json_encode(['id' => 're_test', 'object' => 'refund', 'status' => 'succeeded']), 200, []];
        }
    };
}

afterEach(function () {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

it('refunds the platform fee along with the donation', function () {
    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_refund_test',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 100.00,
        'stripe_charge_id' => 'ch_refund_test',
    ]);

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeRefundClient($requests));

    app(RefundDonation::class)->handle($donation);

    expect($requests)->toHaveCount(1);

    // Stripe keeps the application fee with the platform unless the refund
    // asks for it back, which would leave the NGO paying our fee on a
    // donation that no longer exists.
    // The SDK serialises booleans on the wire, so accept either form.
    expect($requests[0]['params'])->toHaveKey('refund_application_fee')
        ->and($requests[0]['params']['refund_application_fee'])->toBeIn([true, 'true']);

    expect($donation->fresh()->status)->toBe(DonationStatus::Refunded);
});
