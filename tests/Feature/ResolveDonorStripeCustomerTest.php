<?php

declare(strict_types=1);

use App\Actions\Stripe\ResolveDonorStripeCustomer;
use App\Models\Donor;
use App\Models\DonorStripeCustomer;
use App\Models\Organization;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Records every Stripe request and replays canned responses, so tests can
 * assert which connected account each call was addressed to.
 */
function fakeStripeCustomerClient(callable $respond): object
{
    $client = new class($respond) implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, account: string|null, params: array<string, mixed>}> */
        public array $requests = [];

        public function __construct(private $respond) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $account = null;

            foreach ($headers as $header) {
                if (str_starts_with((string) $header, 'Stripe-Account: ')) {
                    $account = substr((string) $header, strlen('Stripe-Account: '));
                }
            }

            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'account' => $account,
                'params' => $params,
            ];

            return ($this->respond)($method, $absUrl, $params);
        }
    };

    ApiRequestor::setHttpClient($client);

    return $client;
}

function stripeCustomerResponse(string $id, array $extra = []): array
{
    return [json_encode(['id' => $id, 'object' => 'customer'] + $extra), 200, []];
}

beforeEach(function () {
    config(['services.stripe.secret' => 'sk_test_fake']);
});

afterEach(function () {
    ApiRequestor::setHttpClient(null);
});

it('creates a separate customer per connected account for the same donor', function () {
    $orgA = Organization::factory()->stripeConnected()->create(['stripe_enabled' => true]);
    $orgB = Organization::factory()->stripeConnected()->create(['stripe_enabled' => true]);
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);

    $created = 0;
    $client = fakeStripeCustomerClient(function () use (&$created) {
        $created++;

        return stripeCustomerResponse('cus_created_'.$created);
    });

    $resolver = app(ResolveDonorStripeCustomer::class);

    $customerA = $resolver->resolve($donor, $orgA);
    $customerB = $resolver->resolve($donor->refresh(), $orgB);

    expect($customerA)->not->toBe($customerB);

    $accounts = collect($client->requests)->pluck('account')->all();

    expect($accounts)->toBe([$orgA->stripe_account_id, $orgB->stripe_account_id])
        ->and(DonorStripeCustomer::query()->where('donor_id', $donor->getKey())->count())->toBe(2);
});

it('reuses the mapped customer without calling stripe again', function () {
    $organization = Organization::factory()->stripeConnected()->create(['stripe_enabled' => true]);
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);

    DonorStripeCustomer::factory()->create([
        'donor_id' => $donor->getKey(),
        'organization_id' => $organization->getKey(),
        'stripe_customer_id' => 'cus_already_mapped',
    ]);

    $client = fakeStripeCustomerClient(fn () => stripeCustomerResponse('cus_should_not_be_created'));

    expect(app(ResolveDonorStripeCustomer::class)->resolve($donor, $organization))->toBe('cus_already_mapped')
        ->and($client->requests)->toBeEmpty();
});

it('adopts the legacy donor customer id when it exists on that account', function () {
    $organization = Organization::factory()->stripeConnected()->create(['stripe_enabled' => true]);
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_legacy_same_account']);

    $client = fakeStripeCustomerClient(fn () => stripeCustomerResponse('cus_legacy_same_account'));

    expect(app(ResolveDonorStripeCustomer::class)->resolve($donor, $organization))->toBe('cus_legacy_same_account');

    expect($client->requests)->toHaveCount(1)
        ->and($client->requests[0]['method'])->toBe('get')
        ->and($client->requests[0]['account'])->toBe($organization->stripe_account_id)
        ->and(DonorStripeCustomer::query()
            ->where('donor_id', $donor->getKey())
            ->where('organization_id', $organization->getKey())
            ->value('stripe_customer_id'))->toBe('cus_legacy_same_account');
});

it('creates a fresh customer when the legacy id belongs to another account', function () {
    $organization = Organization::factory()->stripeConnected()->create(['stripe_enabled' => true]);
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_from_other_account']);

    $client = fakeStripeCustomerClient(function (string $method) {
        // Stripe answers a cross-account lookup with "No such customer".
        if ($method === 'get') {
            return [json_encode(['error' => [
                'type' => 'invalid_request_error',
                'message' => "No such customer: 'cus_from_other_account'",
            ]]), 404, []];
        }

        return stripeCustomerResponse('cus_for_this_account');
    });

    $resolved = app(ResolveDonorStripeCustomer::class)->resolve($donor, $organization);

    expect($resolved)->toBe('cus_for_this_account')
        ->and(collect($client->requests)->pluck('method')->all())->toBe(['get', 'post']);

    // The legacy column must survive untouched; overwriting it is the original bug.
    expect($donor->refresh()->stripe_customer_id)->toBe('cus_from_other_account')
        ->and(DonorStripeCustomer::query()
            ->where('donor_id', $donor->getKey())
            ->where('organization_id', $organization->getKey())
            ->value('stripe_customer_id'))->toBe('cus_for_this_account');
});

it('addresses the platform account when the organization is not fully stripe active', function () {
    $organization = Organization::factory()->stripeConnected()->create(['stripe_enabled' => false]);
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);

    $client = fakeStripeCustomerClient(fn () => stripeCustomerResponse('cus_platform'));

    expect(app(ResolveDonorStripeCustomer::class)->resolve($donor, $organization))->toBe('cus_platform')
        ->and($client->requests[0]['account'])->toBeNull();
});
