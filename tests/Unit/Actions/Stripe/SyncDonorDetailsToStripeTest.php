<?php

declare(strict_types=1);

use App\Actions\Stripe\SyncDonorDetailsToStripe;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Stripe;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function (): void {
    Stripe::setApiKey('sk_test_fake');
});

afterEach(function (): void {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

function fakeStripeClientForDonorUpdate(?array &$requests = null): object
{
    $requests ??= [];

    return new class($requests) implements ClientInterface
    {
        public function __construct(private array &$requests) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'params' => $params,
            ];

            $response = match (true) {
                str_contains($absUrl, '/v1/customers/') && $method === 'post' => [
                    'id' => 'cus_test',
                    'object' => 'customer',
                ],
                str_contains($absUrl, '/v1/subscriptions/') && $method === 'post' => [
                    'id' => 'sub_test',
                    'object' => 'subscription',
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };
}

it('does nothing when donor has no stripe customer id', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeClientForDonorUpdate($requests));

    $result = app(SyncDonorDetailsToStripe::class)->sync($donor, $org);

    expect($result)->toBeFalse()
        ->and($requests)->toBeEmpty();
});

it('updates stripe customer with full name and email', function () {
    $org = Organization::factory()->stripeConnected()->create();
    $donor = Donor::factory()->create([
        'stripe_customer_id' => 'cus_test_123',
        'first_name' => 'Siti',
        'last_name' => 'Aminah',
        'email' => 'siti@example.com',
        'locale' => 'ms',
    ]);

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeClientForDonorUpdate($requests));

    $result = app(SyncDonorDetailsToStripe::class)->sync($donor, $org);

    expect($result)->toBeTrue();

    $customerRequest = collect($requests)->first(fn ($r) => str_contains($r['url'], '/v1/customers/cus_test_123'));

    expect($customerRequest)->not->toBeNull()
        ->and($customerRequest['params']['name'] ?? null)->toBe('Siti Aminah')
        ->and($customerRequest['params']['email'] ?? null)->toBe('siti@example.com');
});

it('updates active and paused subscription metadata for the organization', function () {
    $org = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create([
        'stripe_customer_id' => 'cus_test_123',
        'first_name' => 'Ali',
        'last_name' => 'Abu',
        'email' => 'ali@example.com',
    ]);
    Subscription::factory()->for($donor)->for($campaign)->create([
        'status' => SubscriptionStatus::Active,
        'stripe_subscription_id' => 'sub_active_123',
    ]);
    Subscription::factory()->for($donor)->for($campaign)->create([
        'status' => SubscriptionStatus::Paused,
        'stripe_subscription_id' => 'sub_paused_123',
    ]);

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeClientForDonorUpdate($requests));

    app(SyncDonorDetailsToStripe::class)->sync($donor, $org);

    $subscriptionRequests = collect($requests)
        ->filter(fn ($r) => str_contains($r['url'], '/v1/subscriptions/'));

    expect($subscriptionRequests)->toHaveCount(2);
});

it('returns false when stripe throws but does not bubble exception', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_test_123']);

    $client = new class implements ClientInterface
    {
        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            throw new RuntimeException('Stripe is down');
        }
    };

    ApiRequestor::setHttpClient($client);

    $result = app(SyncDonorDetailsToStripe::class)->sync($donor, $org);

    expect($result)->toBeFalse();
});
