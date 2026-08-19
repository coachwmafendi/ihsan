<?php

declare(strict_types=1);

use App\Actions\Stripe\SyncDonorDetailsToStripe;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\StripeMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Stripe\ApiRequestor;
use Stripe\Exception\ApiConnectionException;
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

function fakeStripeClientForDonorUpdate(array &$requests): object
{
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
                str_contains($absUrl, '/v1/customers/') && $method === 'get' => [
                    'id' => 'cus_test_123',
                    'object' => 'customer',
                ],
                str_contains($absUrl, '/v1/customers/') && $method === 'post' => [
                    'id' => 'cus_test',
                    'object' => 'customer',
                ],
                str_contains($absUrl, '/v1/subscriptions/') && $method === 'get' => $this->subscriptionRetrieveResponse($absUrl),
                str_contains($absUrl, '/v1/subscriptions/') && $method === 'post' => [
                    'id' => 'sub_test',
                    'object' => 'subscription',
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }

        private function subscriptionRetrieveResponse(string $absUrl): array
        {
            $path = parse_url($absUrl, PHP_URL_PATH);
            $id = $path === false || $path === '' ? 'sub_test' : basename($path);

            return [
                'id' => $id,
                'object' => 'subscription',
                'metadata' => [
                    'existing_key' => 'existing_value',
                ],
            ];
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

    $customerRequest = collect($requests)
        ->first(fn ($r) => str_contains($r['url'], '/v1/customers/cus_test_123') && $r['method'] === 'post');

    expect($customerRequest)->not->toBeNull()
        ->and($customerRequest['params']['name'] ?? null)->toBe('Siti Aminah')
        ->and($customerRequest['params']['email'] ?? null)->toBe('siti@example.com')
        ->and($customerRequest['params']['preferred_locales'] ?? null)->toBe(['ms']);
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
    Subscription::factory()->for($donor)->for($campaign)->create([
        'status' => SubscriptionStatus::Cancelled,
        'stripe_subscription_id' => 'sub_cancelled_123',
    ]);

    $otherOrg = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->for($otherOrg)->create();
    Subscription::factory()->for($donor)->for($otherCampaign)->create([
        'status' => SubscriptionStatus::Active,
        'stripe_subscription_id' => 'sub_other_org_123',
    ]);

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeClientForDonorUpdate($requests));

    app(SyncDonorDetailsToStripe::class)->sync($donor, $org);

    $subscriptionUpdateRequests = collect($requests)
        ->filter(fn ($r) => str_contains($r['url'], '/v1/subscriptions/') && $r['method'] === 'post');

    expect($subscriptionUpdateRequests)->toHaveCount(2)
        ->and($subscriptionUpdateRequests->pluck('url'))->each(fn ($url) => $url->not->toContain('sub_other_org_123'))
        ->and($subscriptionUpdateRequests->pluck('url'))->each(fn ($url) => $url->not->toContain('sub_cancelled_123'));

    $expectedMetadata = StripeMetadata::forDonorUpdate($donor);
    $subscriptionUpdateRequests->each(function ($r) use ($expectedMetadata): void {
        $metadata = $r['params']['metadata'] ?? [];

        expect($metadata)->toEqual($expectedMetadata + ['existing_key' => 'existing_value']);
    });
});

it('preserves existing subscription metadata while updating donor metadata', function () {
    $org = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create([
        'stripe_customer_id' => 'cus_test_123',
        'first_name' => 'Muthu',
        'last_name' => 'Kumar',
        'email' => 'muthu@example.com',
    ]);
    Subscription::factory()->for($donor)->for($campaign)->create([
        'status' => SubscriptionStatus::Active,
        'stripe_subscription_id' => 'sub_metadata_123',
    ]);

    $requests = [];
    ApiRequestor::setHttpClient(fakeStripeClientForDonorUpdate($requests));

    app(SyncDonorDetailsToStripe::class)->sync($donor, $org);

    $updateRequest = collect($requests)
        ->first(fn ($r) => str_contains($r['url'], '/v1/subscriptions/sub_metadata_123') && $r['method'] === 'post');

    expect($updateRequest)->not->toBeNull();
    expect($updateRequest['params']['metadata'] ?? null)->toBe(array_merge(
        ['existing_key' => 'existing_value'],
        StripeMetadata::forDonorUpdate($donor),
    ));
});

it('returns false and logs an error when stripe throws', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_test_123']);

    Log::shouldReceive('error')->once();

    $client = new class implements ClientInterface
    {
        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            throw new ApiConnectionException('Stripe is down');
        }
    };

    ApiRequestor::setHttpClient($client);

    $result = app(SyncDonorDetailsToStripe::class)->sync($donor, $org);

    expect($result)->toBeFalse();
});

it('does not update subscriptions when customer update fails', function () {
    $org = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create([
        'stripe_customer_id' => 'cus_test_123',
        'first_name' => 'Fatimah',
        'last_name' => 'Omar',
        'email' => 'fatimah@example.com',
    ]);
    Subscription::factory()->for($donor)->for($campaign)->create([
        'status' => SubscriptionStatus::Active,
        'stripe_subscription_id' => 'sub_skip_123',
    ]);

    Log::shouldReceive('error')->once();

    $requests = [];
    $client = new class($requests) implements ClientInterface
    {
        public function __construct(private array &$requests) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'params' => $params,
            ];

            if (str_contains($absUrl, '/v1/customers/')) {
                throw new ApiConnectionException('Customer update failed');
            }

            return [json_encode(['id' => 'sub_test', 'object' => 'subscription']), 200, []];
        }
    };

    ApiRequestor::setHttpClient($client);

    $result = app(SyncDonorDetailsToStripe::class)->sync($donor, $org);
    $subscriptionRequests = collect($requests)
        ->filter(fn ($r) => str_contains($r['url'], '/v1/subscriptions/'));

    expect($result)->toBeFalse()
        ->and($subscriptionRequests)->toBeEmpty();
});

it('continues updating remaining subscriptions when one subscription update fails', function () {
    $org = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($org)->create();
    $donor = Donor::factory()->create([
        'stripe_customer_id' => 'cus_test_123',
        'first_name' => 'Ahmad',
        'last_name' => 'Bakar',
        'email' => 'ahmad@example.com',
    ]);
    Subscription::factory()->for($donor)->for($campaign)->create([
        'status' => SubscriptionStatus::Active,
        'stripe_subscription_id' => 'sub_fail_123',
    ]);
    Subscription::factory()->for($donor)->for($campaign)->create([
        'status' => SubscriptionStatus::Active,
        'stripe_subscription_id' => 'sub_ok_123',
    ]);

    Log::shouldReceive('error')->once();

    $requests = [];
    $client = new class($requests) implements ClientInterface
    {
        public function __construct(private array &$requests) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'params' => $params,
            ];

            if (str_contains($absUrl, '/v1/subscriptions/sub_fail_123') && $method === 'post') {
                throw new ApiConnectionException('Subscription update failed');
            }

            $response = match (true) {
                str_contains($absUrl, '/v1/customers/') && $method === 'get' => [
                    'id' => 'cus_test_123',
                    'object' => 'customer',
                ],
                str_contains($absUrl, '/v1/customers/') && $method === 'post' => [
                    'id' => 'cus_test',
                    'object' => 'customer',
                ],
                str_contains($absUrl, '/v1/subscriptions/') && $method === 'get' => [
                    'id' => basename(parse_url($absUrl, PHP_URL_PATH)),
                    'object' => 'subscription',
                    'metadata' => [],
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

    ApiRequestor::setHttpClient($client);

    $result = app(SyncDonorDetailsToStripe::class)->sync($donor, $org);

    $subscriptionUpdateRequests = collect($requests)
        ->filter(fn ($r) => str_contains($r['url'], '/v1/subscriptions/') && $r['method'] === 'post');

    expect($result)->toBeFalse()
        ->and($subscriptionUpdateRequests)->toHaveCount(2)
        ->and(collect($requests)->first(fn ($r) => str_contains($r['url'], '/v1/customers/cus_test_123')))->not->toBeNull();
});
