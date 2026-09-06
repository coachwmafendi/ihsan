<?php

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use App\Models\Organization;
use Livewire\Livewire;
use Stripe\PaymentIntent;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()
        ->for($this->organization)
        ->create(['status' => CampaignStatus::Active]);
    $this->element = Element::factory()
        ->for($this->organization)
        ->for($this->campaign)
        ->create([
            'type' => ElementType::Form,
            'config' => [
                'allow_monthly' => true,
                'allow_cover_fee' => true,
            ],
        ]);
});

it('shows cover fee checkbox when allow_cover_fee is true', function () {
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee("I'll cover the transaction costs", false)
        // The cover is an estimate: the real processor fee depends on the card.
        ->assertSee('≈ +', false)
        ->assertSee('so this is an estimate', false)
        ->assertDontSee('100% of your donation', false);
});

it('hides cover fee checkbox when allow_cover_fee is false', function () {
    $this->element->update(['config' => ['allow_monthly' => true, 'allow_cover_fee' => false]]);

    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertDontSee('cover the processing fee', false);
});

it('calculates estimated fee correctly', function () {
    $component = Livewire::test(DonationForm::class, ['element' => $this->element]);

    // The cover is solved for, not added up: the processor takes its cut of
    // the grossed-up total. The test client has no resolvable IP, so the card
    // counts as foreign and carries Stripe's 4% rather than the domestic 3%.
    $component->set('amount', 100);
    expect($component->get('estimatedFee'))->toBe(7.82);

    $component->set('amount', 200);
    expect($component->get('estimatedFee'))->toBe(14.59);

    $component->set('amount', 1);
    expect($component->get('estimatedFee'))->toBe(1.11);
});

it('estimated fee is zero when cover fee is unchecked', function () {
    $component = Livewire::test(DonationForm::class, ['element' => $this->element]);
    $component->set('amount', 200)->set('coverFee', false);

    expect($component->get('estimatedFee'))->toBe(0.0);
});

it('estimated fee is zero when allow_cover_fee config is false', function () {
    $this->element->update(['config' => ['allow_monthly' => true, 'allow_cover_fee' => false]]);
    $component = Livewire::test(DonationForm::class, ['element' => $this->element]);
    $component->set('amount', 200)->set('coverFee', true);

    expect($component->get('estimatedFee'))->toBe(0.0);
});

it('charges gross_amount plus fee to stripe when donor covers fee', function () {
    $mockPaymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_coverfee',
        'client_secret' => 'pi_test_coverfee_secret',
        'status' => 'requires_payment_method',
        'amount' => 21200,
        'currency' => 'myr',
    ]);

    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->once()
        ->withArgs(function (Donation $donation) {
            return $donation->gross_amount === '200.00'
                && $donation->donor_fee_covered === '14.59';
        })
        ->andReturn($mockPaymentIntent);

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 200)
        ->set('coverFee', true)
        ->set('frequency', 'one_time')
        ->set('firstName', 'Ahmad Donor')
        ->set('email', 'ahmad@example.com')
        ->call('submit');

    $donation = Donation::latest()->first();
    expect($donation->gross_amount)->toBe('200.00');
    expect($donation->donor_fee_covered)->toBe('14.59');
});

it('stores zero donor_fee_covered when donor opts out', function () {
    $mockPaymentIntent = PaymentIntent::constructFrom([
        'id' => 'pi_test_nocover',
        'client_secret' => 'pi_test_nocover_secret',
        'status' => 'requires_payment_method',
        'amount' => 20000,
        'currency' => 'myr',
    ]);

    $this->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->once()
        ->andReturn($mockPaymentIntent);

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 200)
        ->set('coverFee', false)
        ->set('frequency', 'one_time')
        ->set('firstName', 'Ahmad Donor')
        ->set('email', 'ahmad@example.com')
        ->call('submit');

    $donation = Donation::latest()->first();
    expect($donation->donor_fee_covered)->toBe('0.00');
});
