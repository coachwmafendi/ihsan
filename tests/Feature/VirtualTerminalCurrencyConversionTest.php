<?php

declare(strict_types=1);

use App\Actions\Stripe\ProcessVirtualTerminalDonation;
use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendNewDonationNotification;
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

function fakeStripeClientForVtDonation(string $paymentIntentId, ?array &$requests = null): object
{
    $requests ??= [];

    return new class($paymentIntentId, $requests) implements ClientInterface
    {
        /** @param array<int, string> $requests */
        public function __construct(private string $paymentIntentId, private array &$requests) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = $method.' '.$absUrl;
            $response = match (true) {
                str_ends_with($absUrl, '/v1/customers') && $method === 'post' => [
                    'id' => 'cus_vt_test',
                    'object' => 'customer',
                ],
                str_contains($absUrl, '/v1/payment_methods/') && str_ends_with($absUrl, '/attach') => [
                    'id' => 'pm_vt_test',
                    'object' => 'payment_method',
                    'customer' => 'cus_vt_test',
                ],
                str_contains($absUrl, '/v1/payment_methods/pm_vt_test') && $method === 'get' => [
                    'id' => 'pm_vt_test',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => ['brand' => 'visa', 'last4' => '4242'],
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

it('attaches the card to the customer before charging', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeClientForVtDonation('pi_vt_attach', $requests));

    $syncSpy = Mockery::mock(SyncDonationStripeDetails::class);
    $syncSpy->shouldReceive('sync')->once()->andReturn(['payment_intent' => null, 'charge_id' => null]);
    app()->instance(SyncDonationStripeDetails::class, $syncSpy);

    app(ProcessVirtualTerminalDonation::class)->handle(
        campaignId: $campaign->id,
        amount: 30.00,
        firstName: 'Ahmad',
        lastName: 'Ali',
        email: 'attach@example.test',
        organization: $organization,
        currency: 'myr',
        paymentMethodId: 'pm_vt_test',
    );

    $attachIndex = collect($requests)->search(fn ($r) => str_contains($r, '/payment_methods/pm_vt_test/attach'));
    $chargeIndex = collect($requests)->search(fn ($r) => str_ends_with($r, '/v1/payment_intents'));

    expect($attachIndex)->not->toBeFalse()
        ->and($chargeIndex)->not->toBeFalse()
        ->and($attachIndex)->toBeLessThan($chargeIndex);
});

it('notifies the organisation of a virtual terminal donation', function () {
    Queue::fake();

    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    ApiRequestor::setHttpClient(fakeStripeClientForVtDonation('pi_vt_notify'));

    $syncSpy = Mockery::mock(SyncDonationStripeDetails::class);
    $syncSpy->shouldReceive('sync')->once()->andReturn(['payment_intent' => null, 'charge_id' => null]);
    app()->instance(SyncDonationStripeDetails::class, $syncSpy);

    app(ProcessVirtualTerminalDonation::class)->handle(
        campaignId: $campaign->id,
        amount: 75.00,
        firstName: 'Ahmad',
        lastName: 'Ali',
        email: 'notify@example.test',
        organization: $organization,
        currency: 'myr',
        paymentMethodId: 'pm_vt_test',
    );

    Queue::assertPushed(SendNewDonationNotification::class);
    Queue::assertPushed(SendLargeDonationNotification::class);
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
