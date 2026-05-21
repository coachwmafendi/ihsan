<?php

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use Livewire\Livewire;
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
        ->assertSee('Your most generous donation')
        ->assertSee('One-time')
        ->assertSee('Monthly')
        ->assertSee('RM 200')
        ->assertSee('RM 5')
        ->assertSee('Donate and Support')
        ->assertSee('x-show="!processing && !success && !error"', false)
        ->assertDontSee('x-show="!processing && !success && !error" x-cloak', false)
        ->assertSee("x-on:click=\"frequency = 'one_time'\"", false)
        ->assertSee("x-on:click=\"frequency = 'monthly'\"", false)
        ->assertDontSee("wire:click=\"selectFrequency('monthly')\"", false)
        ->assertDontSee('wire:click="selectAmount', false)
        ->assertSee('$wire.$set(&#039;frequency&#039;, this.frequency, false)', false)
        ->assertSee('$wire.$set(&#039;amount&#039;, this.amount, false)', false)
        ->assertSee('x-show="processing" x-cloak', false)
        ->assertSee('x-show="success" x-cloak', false)
        ->assertSee('x-show="error" x-cloak', false);
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
