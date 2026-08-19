<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\DonorStripeCustomer;
use App\Models\Organization;
use App\Models\Subscription;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

beforeEach(function () {
    config(['services.stripe.secret' => 'sk_test_fake']);
});

afterEach(function () {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

/**
 * Answers Stripe lookups with a customer that depends on the connected account
 * the request was addressed to.
 *
 * @param  array<string, string>  $customerByAccount
 */
function fakeStripeClientPerAccount(array $customerByAccount): object
{
    $client = new class($customerByAccount) implements ClientInterface
    {
        /** @var array<int, string> */
        public array $requests = [];

        public function __construct(private array $customerByAccount) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $account = null;

            foreach ($headers as $header) {
                if (str_starts_with((string) $header, 'Stripe-Account: ')) {
                    $account = substr((string) $header, strlen('Stripe-Account: '));
                }
            }

            $this->requests[] = $method.' '.$absUrl.' @'.($account ?? 'platform');

            $customer = $this->customerByAccount[$account] ?? null;

            if (str_contains($absUrl, '/v1/payment_methods/')) {
                return [json_encode([
                    'id' => 'pm_backfill',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'customer' => $customer,
                ]), 200, []];
            }

            return [json_encode([
                'id' => 'pi_backfill',
                'object' => 'payment_intent',
                'customer' => $customer,
            ]), 200, []];
        }
    };

    ApiRequestor::setHttpClient($client);

    return $client;
}

it('maps a donor to a different customer for each connected account', function () {
    $orgA = Organization::factory()->stripeConnected()->create();
    $orgB = Organization::factory()->stripeConnected()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => 'cus_only_on_b']);

    $campaignA = Campaign::factory()->for($orgA)->create();
    $campaignB = Campaign::factory()->for($orgB)->create();

    Donation::factory()->for($donor)->for($campaignA)->create(['stripe_payment_intent_id' => 'pi_a']);
    Donation::factory()->for($donor)->for($campaignB)->create(['stripe_payment_intent_id' => 'pi_b']);

    fakeStripeClientPerAccount([
        $orgA->stripe_account_id => 'cus_only_on_a',
        $orgB->stripe_account_id => 'cus_only_on_b',
    ]);

    $this->artisan('app:backfill-donor-stripe-customers')->assertExitCode(0);

    expect(DonorStripeCustomer::query()
        ->where('donor_id', $donor->getKey())
        ->where('organization_id', $orgA->getKey())
        ->value('stripe_customer_id'))->toBe('cus_only_on_a')
        ->and(DonorStripeCustomer::query()
            ->where('donor_id', $donor->getKey())
            ->where('organization_id', $orgB->getKey())
            ->value('stripe_customer_id'))->toBe('cus_only_on_b');
});

it('prefers the subscription payment method over donation payment intents', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);
    $campaign = Campaign::factory()->for($organization)->create();

    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_backfill',
        'brand' => 'Visa',
        'last4' => '4242',
        'exp_month' => 12,
        'exp_year' => 2030,
        'is_default' => true,
    ]);

    Subscription::factory()->for($donor)->for($campaign)->create([
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'stripe_subscription_id' => null,
    ]);

    Donation::factory()->for($donor)->for($campaign)->create(['stripe_payment_intent_id' => 'pi_ignored']);

    $client = fakeStripeClientPerAccount([$organization->stripe_account_id => 'cus_from_pm']);

    $this->artisan('app:backfill-donor-stripe-customers')->assertExitCode(0);

    expect(DonorStripeCustomer::query()->where('donor_id', $donor->getKey())->value('stripe_customer_id'))
        ->toBe('cus_from_pm')
        ->and($client->requests[0])->toContain('/v1/payment_methods/pm_backfill');
});

it('writes nothing on a dry run', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);
    $campaign = Campaign::factory()->for($organization)->create();

    Donation::factory()->for($donor)->for($campaign)->create(['stripe_payment_intent_id' => 'pi_dry']);

    fakeStripeClientPerAccount([$organization->stripe_account_id => 'cus_dry']);

    $this->artisan('app:backfill-donor-stripe-customers', ['--dry-run' => true])->assertExitCode(0);

    expect(DonorStripeCustomer::query()->count())->toBe(0);
});

it('skips pairs that are already mapped', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $donor = Donor::factory()->create(['stripe_customer_id' => null]);
    $campaign = Campaign::factory()->for($organization)->create();

    Donation::factory()->for($donor)->for($campaign)->create(['stripe_payment_intent_id' => 'pi_mapped']);

    DonorStripeCustomer::create([
        'donor_id' => $donor->getKey(),
        'organization_id' => $organization->getKey(),
        'stripe_customer_id' => 'cus_existing',
    ]);

    $client = fakeStripeClientPerAccount([$organization->stripe_account_id => 'cus_other']);

    $this->artisan('app:backfill-donor-stripe-customers')->assertExitCode(0);

    expect($client->requests)->toBeEmpty()
        ->and(DonorStripeCustomer::query()->where('donor_id', $donor->getKey())->value('stripe_customer_id'))
        ->toBe('cus_existing');
});
