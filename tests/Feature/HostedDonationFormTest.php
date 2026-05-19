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
        ->assertSee('Donate and Support');
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
        ->assertSet('submitted', true)
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
