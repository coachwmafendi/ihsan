<?php

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\SubscriptionStatus;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use Livewire\Livewire;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;
use Stripe\PaymentIntent;

it('renders a hosted donation form for an active form element token', function () {
    $organization = Organization::factory()->create([
        'name' => 'Maahad Tahfiz Mumtazatut Taqwa',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'MTMT Development Fund',
        'suggested_amounts' => [30, 50, 100],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'form-token-123',
        'type' => ElementType::Form,
        'config' => [
            'title' => 'Your most generous donation',
            'submit_text' => 'Donate and Support',
            'default_amount' => 5,
            'suggested_amounts' => [200, 100, 50, 30, 10, 5],
            'allow_monthly' => true,
            'show_dedication' => true,
            'show_comment' => true,
        ],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('Maahad Tahfiz Mumtazatut Taqwa')
        ->assertSee('MTMT Development Fund')
        ->assertSee('Secure donation')
        ->assertSee('Give once')
        ->assertSee('Monthly')
        ->assertSee('200')
        ->assertSee('100')
        ->assertSee('50')
        ->assertSee('30')
        ->assertSee('5')
        ->assertSee('Donate monthly')
        ->assertSee('x-show="currentStep === 3"', false)
        ->assertDontSee('x-show="!processing && !success && !error"', false)
        ->assertSee("x-on:click=\"selectFrequency('one_time')\"", false)
        ->assertSee("selectFrequency('monthly')", false)
        ->assertDontSee("x-on:click=\"frequency = 'monthly'", false)
        ->assertSee('x-on:click="selectAmount(amt)"', false)
        ->assertSee('x-show="processing"', false)
        ->assertSee('x-show="currentStep === \'success\'"', false)
        ->assertSee('x-show="currentStep === \'error\'"', false);
});

it('uses the saved secure donation template for standard form elements', function () {
    $organization = Organization::factory()->create([
        'name' => 'PUSAT TAHFIZ ANNUR',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Wakaf Pembangunan Pusat Tahfiz Annur',
        'headline' => 'You Will Make a Difference',
        'short_summary' => 'Your sincere contribution helps complete the development work.',
        'image_path' => 'campaigns/form-hero.png',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'standard-form-token',
        'type' => ElementType::Form,
        'config' => [
            'template' => 'secure-donation',
            'default_amount' => 30,
            'default_frequency' => 'monthly',
        ],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('You Will Make a Difference')
        ->assertSee('Secure donation')
        ->assertSee(route('donations.campaign-image', $element), false)
        ->assertSee('Donate monthly')
        ->assertDontSee('lg:grid-cols-[minmax(0,1fr)_440px]', false);
});

it('uses the saved secure donation template for popup elements', function () {
    $organization = Organization::factory()->create([
        'name' => 'PUSAT TAHFIZ ANNUR',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Wakaf Pembangunan Pusat Tahfiz Annur',
        'headline' => 'You Will Make a Difference',
        'short_summary' => 'Your sincere contribution helps complete the development work.',
        'image_path' => 'campaigns/popup-hero.png',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'popup-element-token',
        'type' => ElementType::Popup,
        'config' => [
            'template' => 'secure-donation',
            'default_amount' => 30,
            'default_frequency' => 'monthly',
        ],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('You Will Make a Difference')
        ->assertSee('Secure donation')
        ->assertSee(route('donations.campaign-image', $element), false)
        ->assertSee('Donate monthly')
        ->assertSee('lg:max-w-6xl', false)
        ->assertSee('bg-black/35 backdrop-blur-[1px]', false)
        ->assertSee('lg:grid-cols-[minmax(0,1fr)_440px]', false)
        ->assertSee('lg:border-r lg:border-slate-200', false);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('isPopup', true)
        ->assertSee('Secure donation');
});

it('keeps popup gradient styles inside the livewire root', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'image_path' => 'campaigns/popup-hero.png',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Popup,
        'config' => [
            'template' => 'secure-donation',
            'button_effect' => 'gradient_blue_purple',
        ],
    ]);

    $html = $this->get(route('donations.show', ['element' => $element, 'popup' => 1]))
        ->assertOk()
        ->getContent();

    $rootPosition = strpos($html, '<div wire:snapshot');
    $gradientPosition = strpos($html, '@keyframes ihsan-grad-gradient_blue_purple');

    expect($rootPosition)->not->toBeFalse()
        ->and($gradientPosition)->not->toBeFalse()
        ->and($rootPosition)->toBeLessThan($gradientPosition);
});

it('renders amount controls with stable alpine bindings for currency switches', function () {
    $organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr', 'usd', 'sgd']],
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [
            'myr' => [
                'one_time' => [50, 100, 200],
                'monthly' => [30, 50, 100],
            ],
            'usd' => [
                'one_time' => [10, 20, 50],
                'monthly' => [30, 50, 100],
            ],
        ],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Popup,
        'config' => ['template' => 'secure-donation'],
    ]);

    $this->get(route('donations.show', ['element' => $element, 'popup' => 1]))
        ->assertOk()
        ->assertSee(':key="amountOptionKey(amt)"', false)
        ->assertSee('isSelectedAmount(amt)', false)
        ->assertSee('x-bind:value="amount"', false);
});

it('hides the left panel on mobile in popup mode', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'image_path' => 'campaigns/test.jpg',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Popup,
        'config' => ['template' => 'secure-donation'],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('hidden lg:flex lg:min-h-0 lg:flex-col lg:border-r lg:border-slate-200', false);
});

it('renders the hosted donation form in a compact layout when embedded', function () {
    $organization = Organization::factory()->create([
        'name' => 'Maahad Tahfiz Mumtazatut Taqwa',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'MTMT Development Fund',
        'suggested_amounts' => [30, 50, 100],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'form-token-compact',
        'type' => ElementType::Form,
    ]);

    $this->get(route('donations.show', ['element' => $element, 'embed' => 1]))
        ->assertOk()
        ->assertSee('MTMT Development Fund')
        ->assertSee('px-4 py-5 sm:px-5', false)
        ->assertDontSee('lg:grid-cols-[minmax(0,1fr)_440px]', false);

    Livewire::withQueryParams(['embed' => 1])
        ->test(DonationForm::class, ['element' => $element])
        ->set('amount', 30)
        ->set('name', '')
        ->set('email', '')
        ->call('submit')
        ->assertSet('isEmbed', true)
        ->assertHasErrors([
            'name' => 'required',
            'email' => 'required',
        ])
        ->assertSee('px-4 py-5 sm:px-5', false)
        ->assertDontSee('lg:grid-cols-[minmax(0,1fr)_440px]', false);
});

it('renders the hosted donation form as an image-led popup', function () {
    $organization = Organization::factory()->create([
        'name' => 'PUSAT TAHFIZ ANNUR',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Wakaf Pembangunan Pusat Tahfiz Annur',
        'headline' => 'You Will Make a Difference',
        'short_summary' => 'Your sincere contribution helps complete the development work.',
        'image_path' => 'campaigns/form-hero.png',
        'suggested_amounts' => [
            'one_time' => [
                ['amount' => 500],
                ['amount' => 200],
                ['amount' => 100],
            ],
            'monthly' => [
                ['amount' => 300],
                ['amount' => 200],
                ['amount' => 30],
            ],
        ],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'popup-form-token',
        'type' => ElementType::Form,
        'config' => [
            'default_amount' => 30,
            'default_frequency' => 'monthly',
            'allow_monthly' => true,
        ],
    ]);

    $this->get(route('donations.show', ['element' => $element, 'popup' => 1]))
        ->assertOk()
        ->assertSee('You Will Make a Difference')
        ->assertSee('Secure donation')
        ->assertSee(route('donations.campaign-image', $element), false)
        ->assertSee('Give once')
        ->assertSee('Monthly')
        ->assertSee('[300,200,30]', false)
        ->assertSee('x-for="amt in currentAmounts"', false)
        ->assertSee('Donate monthly')
        ->assertSee('lg:max-w-6xl', false)
        ->assertSee('lg:grid-cols-[minmax(0,1fr)_440px]', false)
        ->assertSee('lg:border-r lg:border-slate-200', false)
        ->assertDontSee('Your most generous donation');
});

it('passes current donor details to stripe billing details', function () {
    $organization = Organization::factory()->create([
        'name' => 'PUSAT TAHFIZ ANNUR',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Wakaf Pembangunan Pusat Tahfiz Annur',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);

    $this->get(route('donations.show', ['element' => $element, 'popup' => 1]))
        ->assertOk()
        ->assertSee('donorName', false)
        ->assertSee($organization->stripe_account_id, false)
        ->assertSee('stripeAccount: connectedStripeAccountId', false)
        ->assertSee('x-model="donorName"', false)
        ->assertSee('donorEmail', false)
        ->assertSee('x-model="donorEmail"', false)
        ->assertSee('donorPhone', false)
        ->assertSee('x-model="donorPhone"', false)
        ->assertSee('$wire.$set(&#039;name&#039;, this.donorName, false)', false)
        ->assertSee('$wire.$set(&#039;email&#039;, this.donorEmail, false)', false)
        ->assertSee('$wire.$set(&#039;phone&#039;, this.donorPhone, false)', false)
        ->assertSee('stripe.createPaymentMethod', false)
        ->assertSee('name: this.donorName', false)
        ->assertSee('email: this.donorEmail', false)
        ->assertSee('phone: this.donorPhone || undefined', false)
        ->assertSee('receipt_email: this.donorEmail', false)
        ->assertSee('payment_method: paymentMethod.id', false);
});

it('does not render inactive or non form elements', function () {
    $inactiveElement = Element::factory()->create([
        'is_active' => false,
        'token' => 'inactive-form',
        'type' => ElementType::Form,
    ]);
    $buttonElement = Element::factory()->create([
        'token' => 'button-element',
        'type' => ElementType::Button,
    ]);

    $this->get(route('donations.show', $inactiveElement))->assertNotFound();
    $this->get(route('donations.show', $buttonElement))->assertNotFound();
});

it('creates a pending donation from the hosted form', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [200, 100, 50, 30, 10, 5],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'form-token-123',
        'type' => ElementType::Form,
        'config' => [
            'title' => 'Your most generous donation',
            'submit_text' => 'Donate and Support',
            'default_amount' => 5,
        ],
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_mock_123',
        'client_secret' => 'pi_test_mock_123_secret_abc',
    ]);

    $this->mock(CreatePaymentIntent::class, function ($mock) use ($paymentIntent): void {
        $mock->shouldReceive('create')->once()->andReturn($paymentIntent);
    });

    Livewire::test(DonationForm::class, ['element' => $element])
        ->set('frequency', 'monthly')
        ->set('amount', 100)
        ->set('name', 'Wan Mohd Afendi')
        ->set('email', 'wan@example.test')
        ->set('phone', '+60123456789')
        ->set('dedicate', true)
        ->set('comment', 'Semoga dipermudahkan.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertReturned('pi_test_mock_123_secret_abc')
        ->assertSee('Thank you');

    $donor = Donor::query()->where('email', 'wan@example.test')->firstOrFail();
    $donation = Donation::query()->whereBelongsTo($donor)->firstOrFail();

    expect($donor->name)->toBe('Wan Mohd Afendi')
        ->and($donor->phone)->toBe('+60123456789')
        ->and($donation->campaign_id)->toBe($campaign->getKey())
        ->and($donation->gross_amount)->toBe('100.00')
        ->and($donation->currency)->toBe('myr')
        ->and($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->type)->toBe(DonationType::Recurring)
        ->and($donation->donor_message)->toBe('Semoga dipermudahkan.')
        ->and($donation->utm_params)->toMatchArray([
            'element_token' => 'form-token-123',
            'frequency' => 'monthly',
            'dedicate' => true,
        ]);
});

it('creates a pending donation from the embedded form and keeps the compact layout', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [200, 100, 50, 30, 10, 5],
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'embedded-form-token',
        'type' => ElementType::Form,
        'config' => [
            'default_amount' => 30,
        ],
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_embed_123',
        'client_secret' => 'pi_test_embed_123_secret_abc',
    ]);

    $this->mock(CreatePaymentIntent::class, function ($mock) use ($paymentIntent): void {
        $mock->shouldReceive('create')->once()->andReturn($paymentIntent);
    });

    Livewire::withQueryParams(['embed' => 1])
        ->test(DonationForm::class, ['element' => $element])
        ->set('frequency', 'one_time')
        ->set('amount', 30)
        ->set('name', 'Embedded Donor')
        ->set('email', 'embedded@example.test')
        ->call('submit')
        ->assertSet('isEmbed', true)
        ->assertHasNoErrors()
        ->assertReturned('pi_test_embed_123_secret_abc')
        ->assertSee('px-4 py-5 sm:px-5', false)
        ->assertDontSee('lg:grid-cols-[minmax(0,1fr)_440px]', false);

    $donor = Donor::query()->where('email', 'embedded@example.test')->firstOrFail();
    $donation = Donation::query()->whereBelongsTo($donor)->firstOrFail();

    expect($donation->gross_amount)->toBe('30.00')
        ->and($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->type)->toBe(DonationType::OneTime)
        ->and($donation->stripe_payment_intent_id)->toBe('pi_test_embed_123');
});

it('confirms a recurring payment and creates a subscription', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 5],
    ]);

    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_test_confirm_123',
        'gross_amount' => 100.00,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_confirm_123',
        'object' => 'payment_intent',
        'payment_method' => null,
        'charges' => [
            'object' => 'list',
            'data' => [[
                'id' => 'ch_test_123',
                'object' => 'charge',
                'balance_transaction' => null,
                'payment_method_details' => null,
            ]],
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('confirmPayment', 'pi_test_confirm_123', $paymentIntent);

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->stripe_charge_id)->toBe('ch_test_123');

    $subscription = $donation->subscription;
    expect($subscription)->not->toBeNull()
        ->and($subscription->campaign_id)->toBe($campaign->getKey())
        ->and($subscription->donor_id)->toBe($donor->getKey())
        ->and((float) $subscription->amount)->toBe(100.00)
        ->and($subscription->interval->value)->toBe('monthly')
        ->and($subscription->status->value)->toBe('active');
});

it('creates a connected stripe billing subscription after a monthly payment succeeds', function () {
    config(['services.stripe.secret' => 'sk_test_fake']);

    $stripeClient = new class implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>}> */
        public array $requests = [];

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'headers' => $headers,
                'params' => $params,
            ];

            $response = match (true) {
                str_contains($absUrl, '/v1/payment_methods/') => [
                    'id' => 'pm_connected_monthly',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                    ],
                ],
                str_ends_with($absUrl, '/v1/products') => [
                    'id' => 'prod_connected_monthly',
                    'object' => 'product',
                ],
                str_ends_with($absUrl, '/v1/prices') => [
                    'id' => 'price_connected_monthly',
                    'object' => 'price',
                ],
                str_ends_with($absUrl, '/v1/subscriptions') => [
                    'id' => 'sub_connected_monthly',
                    'object' => 'subscription',
                    'status' => 'trialing',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_connected_test',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Connected Monthly Campaign',
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_connected_monthly',
        'gross_amount' => 30.00,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_connected_monthly',
        'object' => 'payment_intent',
        'customer' => 'cus_connected_monthly',
        'payment_method' => 'pm_connected_monthly',
        'latest_charge' => [
            'id' => 'ch_connected_monthly',
            'object' => 'charge',
            'balance_transaction' => null,
        ],
    ]);

    try {
        Livewire::test(DonationForm::class, ['element' => $element])
            ->call('confirmPayment', 'pi_connected_monthly', $paymentIntent);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $donation->refresh();
    $subscription = $donation->subscription;

    expect($subscription)->not->toBeNull()
        ->and($subscription->stripe_subscription_id)->toBe('sub_connected_monthly')
        ->and($subscription->stripe_price_id)->toBe('price_connected_monthly')
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);

    $connectedRequests = collect($stripeClient->requests)
        ->filter(fn (array $request): bool => str_contains($request['url'], '/v1/payment_methods/')
            || str_ends_with($request['url'], '/v1/products')
            || str_ends_with($request['url'], '/v1/prices')
            || str_ends_with($request['url'], '/v1/subscriptions'));

    expect($connectedRequests)->toHaveCount(4)
        ->and($connectedRequests->every(fn (array $request): bool => in_array('Stripe-Account: acct_connected_test', $request['headers'], true)))->toBeTrue();

    $subscriptionRequest = collect($stripeClient->requests)
        ->first(fn (array $request): bool => str_ends_with($request['url'], '/v1/subscriptions'));

    expect($subscriptionRequest['params'])->toMatchArray([
        'customer' => 'cus_connected_monthly',
        'default_payment_method' => 'pm_connected_monthly',
    ])
        ->and($subscriptionRequest['params'])->not->toHaveKey('application_fee_percent')
        ->and($subscriptionRequest['params']['trial_end'])->toBeGreaterThan(now()->timestamp);
});

it('stores the latest charge id when confirming a payment', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 5],
    ]);

    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_test_latest_charge',
        'gross_amount' => 100.00,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_latest_charge',
        'object' => 'payment_intent',
        'payment_method' => null,
        'latest_charge' => [
            'id' => 'ch_latest_123',
            'object' => 'charge',
            'balance_transaction' => null,
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('confirmPayment', 'pi_test_latest_charge', $paymentIntent);

    expect($donation->refresh()->stripe_charge_id)->toBe('ch_latest_123');
});

it('stores connected stripe fees and card details when confirming a payment', function () {
    config(['services.stripe.secret' => 'sk_test_fake']);

    $stripeClient = new class implements ClientInterface
    {
        /** @var array<int, array{method: string, url: string, headers: array<int, string>, params: array<string, mixed>}> */
        public array $requests = [];

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requests[] = [
                'method' => $method,
                'url' => $absUrl,
                'headers' => $headers,
                'params' => $params,
            ];

            $response = match (true) {
                str_contains($absUrl, '/v1/payment_methods/') => [
                    'id' => 'pm_connected_fee',
                    'object' => 'payment_method',
                    'type' => 'card',
                    'card' => [
                        'brand' => 'visa',
                        'last4' => '4242',
                    ],
                ],
                str_contains($absUrl, '/v1/balance_transactions/txn_connected_fee') => [
                    'id' => 'txn_connected_fee',
                    'object' => 'balance_transaction',
                    'fee' => 1909,
                    'fee_details' => [
                        [
                            'amount' => 904,
                            'currency' => 'myr',
                            'type' => 'stripe_fee',
                        ],
                        [
                            'amount' => 1005,
                            'currency' => 'myr',
                            'type' => 'application_fee',
                        ],
                    ],
                ],
                str_contains($absUrl, '/v1/charges/ch_connected_fee') => [
                    'id' => 'ch_connected_fee',
                    'object' => 'charge',
                    'balance_transaction' => [
                        'id' => 'txn_connected_fee',
                        'object' => 'balance_transaction',
                        'fee' => 1909,
                    ],
                ],
                str_contains($absUrl, '/v1/payment_intents/pi_connected_fee') => [
                    'id' => 'pi_connected_fee',
                    'object' => 'payment_intent',
                    'customer' => 'cus_connected_fee',
                    'payment_method' => 'pm_connected_fee',
                    'latest_charge' => [
                        'id' => 'ch_connected_fee',
                        'object' => 'charge',
                        'balance_transaction' => null,
                    ],
                    'charges' => [
                        'object' => 'list',
                        'data' => [],
                    ],
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_connected_test',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 5],
    ]);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_connected_fee',
        'gross_amount' => 201,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
    ]);

    try {
        Livewire::test(DonationForm::class, ['element' => $element])
            ->call('confirmPayment', 'pi_connected_fee');
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded)
        ->and($donation->stripe_charge_id)->toBe('ch_connected_fee')
        ->and($donation->payment_method_brand)->toBe('visa')
        ->and($donation->payment_method_type)->toBe('card')
        ->and($donation->stripe_fee)->toBe('9.04')
        ->and($donation->processing_fee)->toBe('10.05')
        ->and($donation->net_amount)->toBe('181.91');

    expect(collect($stripeClient->requests)->every(
        fn (array $request): bool => in_array('Stripe-Account: acct_connected_test', $request['headers'], true),
    ))->toBeTrue();
});

it('does not create a subscription for one-time donations', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 5],
    ]);

    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'stripe_payment_intent_id' => 'pi_test_one_456',
        'gross_amount' => 50.00,
        'currency' => 'myr',
        'status' => DonationStatus::Pending,
        'type' => DonationType::OneTime,
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_one_456',
        'object' => 'payment_intent',
        'payment_method' => null,
        'charges' => [
            'object' => 'list',
            'data' => [[
                'id' => 'ch_test_456',
                'object' => 'charge',
                'balance_transaction' => null,
                'payment_method_details' => null,
            ]],
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->call('confirmPayment', 'pi_test_one_456', $paymentIntent);

    $donation->refresh();

    expect($donation->status)->toBe(DonationStatus::Succeeded);

    expect($donation->subscription)->toBeNull();
});

it('renders step state variables in Alpine donationForm', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 50],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('currentStep', false)
        ->assertSee('stepErrors', false)
        ->assertSee('nextStep()', false)
        ->assertSee('prevStep()', false)
        ->assertDontSee('get success()', false)
        ->assertDontSee('get error()', false)
        ->assertDontSee('get errorMessage()', false);
});

it('renders donor details fields for step 2', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => [
            'show_dedication' => true,
            'show_comment' => true,
        ],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('x-show="currentStep === 2"', false)
        ->assertSee('x-model="donorName"', false)
        ->assertSee('x-model="donorEmail"', false)
        ->assertSee('x-model="donorPhone"', false)
        ->assertSee('Dedicate this donation')
        ->assertSee('Leave a message...');
});

it('renders payment step with summary bar and card element', function () {
    $organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_test_123',
        'stripe_onboarded' => true,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => ['default_amount' => 50, 'default_frequency' => 'monthly'],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('x-show="currentStep === 3"', false)
        ->assertSee('id="card-element"', false)
        ->assertSee('Donate monthly')
        ->assertSee('handleSubmit', false)
        ->assertSee('stripe.createPaymentMethod', false);
});

it('renders a hosted donation form for an active campaign without element', function () {
    $organization = Organization::factory()->create([
        'name' => 'Maahad Tahfiz Mumtazatut Taqwa',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Campaign Direct Fund',
        'status' => CampaignStatus::Active,
        'checkout_modal_enabled' => true,
        'form_parameter' => 'CAMPAIGN2026',
        'suggested_amounts' => [30, 50, 100],
    ]);

    $this->get(route('donations.campaign-show', ['campaign' => $campaign->form_parameter]))
        ->assertOk()
        ->assertSee('Maahad Tahfiz Mumtazatut Taqwa')
        ->assertSee('Campaign Direct Fund')
        ->assertSee('x-on:click="selectAmount(amt)"', false);
});

it('tracks element source in utm_params for all element types', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [200, 100, 50, 30, 10, 5],
    ]);

    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'utm-test-token',
        'type' => ElementType::Button,
        'name' => 'Donation Button A',
        'config' => ['default_amount' => 50, 'allow_monthly' => false],
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_utm_test_456',
        'client_secret' => 'pi_utm_test_456_secret_def',
    ]);

    $this->mock(CreatePaymentIntent::class, function ($mock) use ($paymentIntent): void {
        $mock->shouldReceive('create')->once()->andReturn($paymentIntent);
    });

    Livewire::test(DonationForm::class, ['element' => $element])
        ->set('frequency', 'one_time')
        ->set('amount', 50)
        ->set('name', 'Test Donor')
        ->set('email', 'test@utm.test')
        ->call('submit')
        ->assertHasNoErrors();

    $donation = Donation::query()->whereHas('donor', fn ($q) => $q->where('email', 'test@utm.test'))->firstOrFail();

    expect($donation->utm_params)->toMatchArray([
        'source' => 'element',
        'element_id' => $element->getKey(),
        'element_token' => 'utm-test-token',
        'element_type' => 'button',
        'element_name' => 'Donation Button A',
        'frequency' => 'one_time',
        'dedicate' => false,
    ]);
});

it('validates hosted donation input before creating records', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'config' => [
            'default_amount' => 5,
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->set('amount', 0)
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->call('submit')
        ->assertHasErrors([
            'amount' => 'min',
            'name' => 'required',
            'email' => 'email',
        ]);

    expect(Donation::query()->count())->toBe(0)
        ->and(Donor::query()->count())->toBe(0);
});
