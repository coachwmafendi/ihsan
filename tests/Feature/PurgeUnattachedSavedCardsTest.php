<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
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

/**
 * @param  array<string, string|null>  $attachment  map of payment_method_id => customer (null = unattached)
 */
function fakeStripeClientForSavedCards(array $attachment): object
{
    return new class($attachment) implements ClientInterface
    {
        /** @param array<string, string|null> $attachment */
        public function __construct(private array $attachment) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            foreach ($this->attachment as $pmId => $customer) {
                if (str_ends_with($absUrl, '/v1/payment_methods/'.$pmId)) {
                    return [json_encode(['id' => $pmId, 'object' => 'payment_method', 'customer' => $customer]), 200, []];
                }
            }

            // Unknown payment method — behave like Stripe's "No such payment_method".
            return [json_encode(['error' => ['type' => 'invalid_request_error', 'message' => 'No such payment_method']]), 404, []];
        }
    };
}

function makeSavedCard(Organization $organization, string $paymentMethodId): DonorPaymentMethod
{
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($campaign)->for($donor)->create();

    return DonorPaymentMethod::create([
        'donor_id' => $donor->id,
        'stripe_payment_method_id' => $paymentMethodId,
        'brand' => 'Visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2035,
    ]);
}

it('purges saved cards that are not attached on stripe', function () {
    $organization = Organization::factory()->stripeConnected()->create();

    $attached = makeSavedCard($organization, 'pm_attached');
    $unattached = makeSavedCard($organization, 'pm_unattached');

    ApiRequestor::setHttpClient(fakeStripeClientForSavedCards([
        'pm_attached' => 'cus_x',
        'pm_unattached' => null,
    ]));

    $this->artisan('donors:purge-unattached-cards')->assertSuccessful();

    expect(DonorPaymentMethod::whereKey($attached->id)->exists())->toBeTrue()
        ->and(DonorPaymentMethod::whereKey($unattached->id)->exists())->toBeFalse();
});

it('purges a saved card whose payment method no longer exists on stripe', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $missing = makeSavedCard($organization, 'pm_missing');

    ApiRequestor::setHttpClient(fakeStripeClientForSavedCards([]));

    $this->artisan('donors:purge-unattached-cards')->assertSuccessful();

    expect(DonorPaymentMethod::whereKey($missing->id)->exists())->toBeFalse();
});

it('keeps unattached cards on a dry run', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $unattached = makeSavedCard($organization, 'pm_dry');

    ApiRequestor::setHttpClient(fakeStripeClientForSavedCards(['pm_dry' => null]));

    $this->artisan('donors:purge-unattached-cards --dry-run')->assertSuccessful();

    expect(DonorPaymentMethod::whereKey($unattached->id)->exists())->toBeTrue();
});
