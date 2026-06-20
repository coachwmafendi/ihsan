<?php

use App\Models\Donor;
use Illuminate\Support\Facades\Artisan;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

it('syncs donor locale to stripe customer records', function () {
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

            return [json_encode(['id' => 'cus_test', 'object' => 'customer']), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $donorWithLocale = Donor::factory()->create([
        'name' => 'Ahmad Ali',
        'email' => 'ahmad@example.test',
        'locale' => 'ms',
        'stripe_customer_id' => 'cus_with_locale',
    ]);

    $donorWithoutLocale = Donor::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.test',
        'locale' => null,
        'stripe_customer_id' => 'cus_without_locale',
    ]);

    try {
        $exitCode = Artisan::call('app:sync-donor-locale-to-stripe', ['--default' => 'en']);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    expect($exitCode)->toBe(0)
        ->and($stripeClient->requests)->toHaveCount(2)
        ->and(collect($stripeClient->requests)->pluck('url'))->toContain(
            'https://api.stripe.com/v1/customers/cus_with_locale',
            'https://api.stripe.com/v1/customers/cus_without_locale',
        );

    $withLocaleRequest = collect($stripeClient->requests)
        ->first(fn (array $request): bool => str_ends_with($request['url'], 'cus_with_locale'));
    $withoutLocaleRequest = collect($stripeClient->requests)
        ->first(fn (array $request): bool => str_ends_with($request['url'], 'cus_without_locale'));

    expect($withLocaleRequest['params']['preferred_locales'])->toBe(['ms'])
        ->and($withoutLocaleRequest['params']['preferred_locales'])->toBe(['en']);
});

it('skips donors without locale when no default is provided', function () {
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

            return [json_encode(['id' => 'cus_test', 'object' => 'customer']), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    Donor::factory()->create([
        'locale' => null,
        'stripe_customer_id' => 'cus_no_locale',
    ]);

    try {
        $exitCode = Artisan::call('app:sync-donor-locale-to-stripe');
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    expect($exitCode)->toBe(0)
        ->and($stripeClient->requests)->toHaveCount(0);
});
