<?php

declare(strict_types=1);

use App\Actions\Stripe\ProcessVirtualTerminalDonation;
use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Stripe;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Stripe::setApiKey('sk_test_fake');
});

afterEach(function (): void {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

function fakeStripeClientForVtDonation(string $paymentIntentId): object
{
    return new class($paymentIntentId) implements ClientInterface
    {
        public function __construct(private string $paymentIntentId) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $response = match (true) {
                str_ends_with($absUrl, '/v1/customers') && $method === 'post' => [
                    'id' => 'cus_vt_test',
                    'object' => 'customer',
                ],
                str_ends_with($absUrl, '/v1/payment_intents') && $method === 'post' => [
                    'id' => $this->paymentIntentId,
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'payment_method' => 'pm_vt_test',
                    'latest_charge' => 'ch_vt_test',
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };
}

it('delegates foreign-currency conversion to the stripe details sync', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    ApiRequestor::setHttpClient(fakeStripeClientForVtDonation('pi_vt_test'));

    // The balance-transaction maths lives in (and is tested by) SyncDonationStripeDetails.
    // Here we only assert the Virtual Terminal delegates to it and does not persist the
    // raw foreign amount as the MYR base amount.
    $syncSpy = Mockery::mock(SyncDonationStripeDetails::class);
    $syncSpy->shouldReceive('sync')
        ->once()
        ->withArgs(fn (Donation $donation) => strtolower($donation->currency) === 'sgd')
        ->andReturn(['payment_intent' => null, 'charge_id' => 'ch_vt_test']);
    app()->instance(SyncDonationStripeDetails::class, $syncSpy);

    $donation = app(ProcessVirtualTerminalDonation::class)->handle(
        campaignId: $campaign->id,
        amount: 16.00,
        firstName: 'Hajan',
        lastName: 'Zamzam',
        email: 'sgd@example.test',
        organization: $organization,
        currency: 'sgd',
        paymentMethodId: 'pm_vt_test',
    );

    expect($donation->currency)->toBe('sgd')
        ->and($donation->gross_amount)->toBe('16.00')
        ->and($donation->base_amount)->toBeNull();
});

it('keeps base amount equal to gross for MYR donations', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    ApiRequestor::setHttpClient(fakeStripeClientForVtDonation('pi_vt_myr'));

    $syncSpy = Mockery::mock(SyncDonationStripeDetails::class);
    $syncSpy->shouldReceive('sync')->once()->andReturn(['payment_intent' => null, 'charge_id' => null]);
    app()->instance(SyncDonationStripeDetails::class, $syncSpy);

    $donation = app(ProcessVirtualTerminalDonation::class)->handle(
        campaignId: $campaign->id,
        amount: 50.00,
        firstName: 'Ahmad',
        lastName: 'Ali',
        email: 'myr@example.test',
        organization: $organization,
        currency: 'myr',
        paymentMethodId: 'pm_vt_test',
    );

    expect($donation->base_amount)->toBe('50.00');
});
