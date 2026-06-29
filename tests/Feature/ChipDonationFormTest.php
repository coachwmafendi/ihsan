<?php

use App\Actions\Chip\ChipApiFactory;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\PaymentGateway;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use Chip\ChipApi;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Livewire\Livewire;

beforeEach(fn () => ChipApiFactory::resetFake());

afterEach(fn () => ChipApiFactory::resetFake());

it('creates a chip purchase and stores checkout details for a chip campaign', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'payment_gateway' => PaymentGateway::Chip,
        'checkout_modal_enabled' => true,
    ]);

    $requestHistory = [];
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE123',
            'checkout_url' => 'https://gate.chip-in.asia/pay/PURCHASE123',
        ])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($requestHistory));

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    ChipApiFactory::fake(make: fn () => $chipApi);

    Livewire::test(DonationForm::class, ['campaign' => $campaign])
        ->set('amount', 50)
        ->set('firstName', 'Ali')
        ->set('lastName', 'Bakar')
        ->set('email', 'ali@example.com')
        ->set('currency', 'myr')
        ->set('frequency', 'one_time')
        ->set('coverFee', false)
        ->call('submit')
        ->assertHasNoErrors();

    $donation = Donation::query()->latest()->first();

    expect($donation)->not->toBeNull()
        ->and($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->type)->toBe(DonationType::OneTime)
        ->and($donation->chip_purchase_id)->toBe('PURCHASE123')
        ->and($donation->chip_checkout_url)->toBe('https://gate.chip-in.asia/pay/PURCHASE123');

    expect($requestHistory)->toHaveCount(1);
    $requestBody = json_decode((string) $requestHistory[0]['request']->getBody(), true);
    $requestJson = (string) $requestHistory[0]['request']->getBody();
    expect($requestBody['purchase']['products'][0]['price'])->toBe(5000)
        ->and($requestBody['force_recurring'] ?? false)->toBeFalse()
        ->and($requestJson)->toContain('return_to=');
});

it('creates a recurring chip purchase with force recurring enabled', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
        'settings' => ['chip_payment_methods' => ['card']],
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'payment_gateway' => PaymentGateway::Chip,
        'allow_recurring' => true,
        'checkout_modal_enabled' => true,
    ]);

    $requestHistory = [];
    $mockHandler = new MockHandler([
        new Response(200, [], json_encode([
            'id' => 'PURCHASE_RECURRING',
            'checkout_url' => 'https://gate.chip-in.asia/pay/PURCHASE_RECURRING',
        ])),
    ]);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($requestHistory));

    $chipApi = new ChipApi(
        brandId: $organization->chip_brand_id,
        apiKey: $organization->chip_api_key,
        config: ['handler' => $handlerStack],
    );

    ChipApiFactory::fake(make: fn () => $chipApi);

    Livewire::test(DonationForm::class, ['campaign' => $campaign])
        ->set('amount', 30)
        ->set('firstName', 'Siti')
        ->set('lastName', 'Aminah')
        ->set('email', 'siti@example.com')
        ->set('currency', 'myr')
        ->set('frequency', 'monthly')
        ->call('submit')
        ->assertHasNoErrors();

    $donation = Donation::query()->latest()->first();

    expect($donation->type)->toBe(DonationType::Recurring);

    $requestBody = json_decode((string) $requestHistory[0]['request']->getBody(), true);
    expect($requestBody['force_recurring'] ?? false)->toBeTrue()
        ->and($requestBody['payment_method_whitelist'])->toBe(['visa', 'mastercard', 'maestro', 'mpgs_apple_pay', 'mpgs_google_pay']);
});

it('fails the donation when the organization is not chip onboarded', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => null,
        'chip_api_key' => null,
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'payment_gateway' => PaymentGateway::Chip,
        'checkout_modal_enabled' => true,
    ]);

    $component = Livewire::test(DonationForm::class, ['campaign' => $campaign])
        ->set('amount', 20)
        ->set('firstName', 'Ahmad')
        ->set('lastName', 'Ismail')
        ->set('email', 'ahmad@example.com')
        ->set('currency', 'myr')
        ->set('frequency', 'one_time')
        ->set('coverFee', false);

    $component->call('submit');

    expect($component->get('chipErrorMessage'))->toContain('CHIP is not configured');

    $donation = Donation::query()->latest()->first();

    expect($donation)->not->toBeNull()
        ->and($donation->status)->toBe(DonationStatus::Failed);
});

it('renders a chip public donation page', function () {
    $organization = Organization::factory()->create([
        'chip_brand_id' => 'BRAND123',
        'chip_api_key' => 'secret',
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'payment_gateway' => PaymentGateway::Chip,
        'campaign_page_enabled' => true,
    ]);

    $this->get(route('campaigns.public', $campaign))
        ->assertOk()
        ->assertSee($campaign->title);
});
