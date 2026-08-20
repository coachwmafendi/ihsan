<?php

declare(strict_types=1);

use App\Actions\Stripe\SyncPayout;
use App\Models\Organization;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Stripe;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['services.stripe.secret' => 'sk_test_fake']);
    Stripe::setApiKey('sk_test_fake');
});

afterEach(function (): void {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

it('saves bank name and last4 from expanded destination', function () {
    $org = Organization::factory()->stripeConnected()->create();

    $payout = app(SyncPayout::class)->sync($org, [
        'id' => 'po_test_123',
        'amount' => 10000,
        'currency' => 'myr',
        'status' => 'paid',
        'arrival_date' => now()->timestamp,
        'destination' => [
            'id' => 'ba_test',
            'object' => 'bank_account',
            'bank_name' => 'RHB Bank',
            'last4' => '6578',
        ],
        'metadata' => [],
    ]);

    expect($payout)->toBeInstanceOf(Payout::class)
        ->and($payout->bank_name)->toBe('RHB Bank')
        ->and($payout->bank_account_last4)->toBe('6578');
});

it('falls back to card brand and last4 for card destinations', function () {
    $org = Organization::factory()->stripeConnected()->create();

    $payout = app(SyncPayout::class)->sync($org, [
        'id' => 'po_test_card',
        'amount' => 5000,
        'currency' => 'myr',
        'status' => 'paid',
        'arrival_date' => now()->timestamp,
        'destination' => [
            'id' => 'card_test',
            'object' => 'card',
            'brand' => 'Visa',
            'last4' => '4242',
        ],
        'metadata' => [],
    ]);

    expect($payout->bank_name)->toBe('Visa')
        ->and($payout->bank_account_last4)->toBe('4242');
});

it('fetches external account when destination is an id string', function () {
    $org = Organization::factory()->stripeConnected()->create();

    $requests = [];
    ApiRequestor::setHttpClient(new class($requests) implements ClientInterface
    {
        public function __construct(public array &$requests) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = $method.' '.$absUrl;

            $response = match (true) {
                str_contains($absUrl, '/v1/accounts/') && str_contains($absUrl, '/external_accounts/') => [
                    'id' => 'ba_test_123',
                    'object' => 'bank_account',
                    'bank_name' => 'RHB Bank',
                    'last4' => '6578',
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    });

    $payout = app(SyncPayout::class)->sync($org, [
        'id' => 'po_test_456',
        'amount' => 20000,
        'currency' => 'myr',
        'status' => 'paid',
        'arrival_date' => now()->timestamp,
        'destination' => 'ba_test_123',
        'metadata' => [],
    ]);

    expect($payout->bank_name)->toBe('RHB Bank')
        ->and($payout->bank_account_last4)->toBe('6578');

    expect(collect($requests)->contains(fn ($r) => str_contains($r, '/v1/accounts/'.$org->stripe_account_id.'/external_accounts/ba_test_123')))->toBeTrue();
});
