<?php

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Organization;
use Livewire\Livewire;
use Stripe\PaymentIntent;

it('uses myr as default currency', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
        'config' => [
            'suggested_amounts' => [200, 100, 50, 30, 10, 5],
            'allow_monthly' => true,
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'myr');
});

it('selects currency and updates suggested amounts', function () {
    $organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr', 'usd', 'sgd']],
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [
            'myr' => [
                'one_time' => [['amount' => '30'], ['amount' => '50']],
                'monthly' => [['amount' => '5'], ['amount' => '10']],
                'default_monthly' => '25',
            ],
            'usd' => [
                'one_time' => [['amount' => '10'], ['amount' => '20']],
                'monthly' => [['amount' => '2'], ['amount' => '5']],
                'default_monthly' => '10',
            ],
            'sgd' => [
                'one_time' => [['amount' => '10'], ['amount' => '20']],
                'monthly' => [['amount' => '2'], ['amount' => '5']],
                'default_monthly' => '10',
            ],
            'impact_enabled' => false,
        ],
    ]);

    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
        'config' => [
            'suggested_amounts' => [200, 100, 50, 30, 10, 5],
            'allow_monthly' => false,
        ],
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'myr')
        ->set('frequency', 'one_time')
        ->set('amount', 30)
        ->call('selectCurrency', 'usd')
        ->assertSet('currency', 'usd')
        ->assertSet('amount', 30);
});

it('rejects unsupported currency selection', function () {
    $organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr']],
    ]);

    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
    ]);

    Livewire::test(DonationForm::class, ['element' => $element])
        ->assertSet('currency', 'myr')
        ->call('selectCurrency', 'usd')
        ->assertSet('currency', 'myr');
});

it('saves donation with selected currency', function () {
    $organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr', 'usd']],
    ]);

    $campaign = Campaign::factory()->for($organization)->create([
        'suggested_amounts' => [
            'myr' => [
                'one_time' => [['amount' => '30']],
                'monthly' => [['amount' => '5']],
                'default_monthly' => '25',
            ],
            'usd' => [
                'one_time' => [['amount' => '10']],
                'monthly' => [['amount' => '2']],
                'default_monthly' => '10',
            ],
        ],
    ]);

    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
        'config' => [
            'suggested_amounts' => [200, 100, 50, 30, 10, 5],
            'allow_monthly' => true,
        ],
    ]);

    $paymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_multi_currency_123',
        'client_secret' => 'pi_test_multi_currency_123_secret',
    ]);

    $this->mock(CreatePaymentIntent::class, function ($mock) use ($paymentIntent): void {
        $mock->shouldReceive('create')->once()->andReturn($paymentIntent);
    });

    Livewire::test(DonationForm::class, ['element' => $element])
        ->set('currency', 'usd')
        ->set('amount', 50)
        ->set('frequency', 'one_time')
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '0123456789')
        ->call('submit')
        ->assertHasNoErrors();

    $donation = Donation::where('gross_amount', 50.00)->first();

    expect($donation)->not->toBeNull()
        ->and($donation->currency)->toBe('usd')
        ->and((float) $donation->gross_amount)->toBe(50.00);
});

it('renders currency selector when multiple currencies accepted', function () {
    $organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr', 'usd', 'sgd']],
    ]);

    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
        'config' => ['suggested_amounts' => [200, 100, 50, 30, 10, 5]],
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('RM')
        ->assertSee('$');
});

it('hides currency selector when only MYR accepted', function () {
    $organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr']],
    ]);

    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
        'is_active' => true,
    ]);

    $this->get(route('donations.show', $element))
        ->assertOk()
        ->assertSee('RM');
});
