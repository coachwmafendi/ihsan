<?php

declare(strict_types=1);

use App\Actions\Stripe\ProcessVirtualTerminalSubscription;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\Stripe;

uses(RefreshDatabase::class);

function fakeStripePaymentMethodClient(array $card): ClientInterface
{
    return new class($card) implements ClientInterface
    {
        public function __construct(private array $card) {}

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
        {
            $response = [
                'id' => 'pm_vt_card',
                'object' => 'payment_method',
                'type' => 'card',
                'card' => $this->card,
            ];

            return [json_encode($response), 200, []];
        }
    };
}

function invokeSyncPaymentMethod(Donor $donor): void
{
    $action = app(ProcessVirtualTerminalSubscription::class);
    $method = new ReflectionMethod($action, 'syncPaymentMethod');
    $method->invoke($action, $donor, 'pm_vt_card', []);
}

beforeEach(function () {
    Stripe::setApiKey('sk_test_virtual_terminal');
});

afterEach(function () {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

it('refreshes a saved card that Stripe has since updated', function () {
    $donor = Donor::factory()->create();
    DonorPaymentMethod::create([
        'donor_id' => $donor->getKey(),
        'stripe_payment_method_id' => 'pm_vt_card',
        'brand' => 'Visa',
        'last4' => '4242',
        'exp_month' => 1,
        'exp_year' => 2026,
    ]);

    ApiRequestor::setHttpClient(fakeStripePaymentMethodClient([
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 11,
        'exp_year' => 2031,
        'country' => 'MY',
    ]));

    invokeSyncPaymentMethod($donor);

    $card = DonorPaymentMethod::query()->where('stripe_payment_method_id', 'pm_vt_card')->sole();

    expect((int) $card->exp_month)->toBe(11)
        ->and((int) $card->exp_year)->toBe(2031);
});

it('moves a saved card to the donor who just used it', function () {
    $previousDonor = Donor::factory()->create();
    $currentDonor = Donor::factory()->create();

    DonorPaymentMethod::create([
        'donor_id' => $previousDonor->getKey(),
        'stripe_payment_method_id' => 'pm_vt_card',
        'brand' => 'Visa',
        'last4' => '4242',
        'exp_month' => 4,
        'exp_year' => 2030,
    ]);

    ApiRequestor::setHttpClient(fakeStripePaymentMethodClient([
        'brand' => 'visa',
        'last4' => '4242',
        'exp_month' => 4,
        'exp_year' => 2030,
        'country' => 'MY',
    ]));

    invokeSyncPaymentMethod($currentDonor);

    $card = DonorPaymentMethod::query()->where('stripe_payment_method_id', 'pm_vt_card')->sole();

    expect($card->donor_id)->toBe($currentDonor->getKey())
        ->and(DonorPaymentMethod::query()->count())->toBe(1);
});

it('stores a card the first time the virtual terminal sees it', function () {
    $donor = Donor::factory()->create();

    ApiRequestor::setHttpClient(fakeStripePaymentMethodClient([
        'brand' => 'mastercard',
        'last4' => '5555',
        'exp_month' => 7,
        'exp_year' => 2029,
        'country' => 'MY',
    ]));

    invokeSyncPaymentMethod($donor);

    $card = DonorPaymentMethod::query()->where('stripe_payment_method_id', 'pm_vt_card')->sole();

    expect($card->donor_id)->toBe($donor->getKey())
        ->and($card->brand)->toBe('Mastercard')
        ->and($card->last4)->toBe('5555');
});
