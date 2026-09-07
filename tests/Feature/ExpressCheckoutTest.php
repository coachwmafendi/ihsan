<?php

declare(strict_types=1);

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\PaymentGateway;
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
use Stripe\Stripe;

/**
 * A wallet button hands back the payer's name and email, which is everything
 * the second step of the form collects. The express path fills those in and
 * runs the ordinary submit, so it inherits the same validation, fraud checks,
 * fee calculation and pending-attempt reuse rather than growing a second
 * version of them.
 */
beforeEach(function () {
    Stripe::setApiKey('sk_test_express');
    config(['services.stripe.secret' => 'sk_test_express']);

    $this->organization = Organization::factory()->create([
        'stripe_account_id' => 'acct_express',
        'stripe_onboarded' => true,
        'stripe_enabled' => true,
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $this->element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Form,
        'config' => ['allow_monthly' => true, 'allow_cover_fee' => true],
    ]);
});

afterEach(function () {
    ApiRequestor::setHttpClient(CurlClient::instance());
});

/**
 * Reuse asks Stripe whether the earlier intent can still be paid.
 */
function fakeExpressIntentLookup(): void
{
    ApiRequestor::setHttpClient(new class implements ClientInterface
    {
        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
        {
            return [json_encode([
                'id' => 'pi_express',
                'object' => 'payment_intent',
                'status' => 'requires_payment_method',
                'client_secret' => 'pi_express_secret',
            ]), 200, []];
        }
    });
}

function mockIntent(): void
{
    test()->mock(CreatePaymentIntent::class)
        ->shouldReceive('create')
        ->andReturn(PaymentIntent::constructFrom([
            'id' => 'pi_express',
            'client_secret' => 'pi_express_secret',
            'status' => 'requires_payment_method',
        ]));
}

it('records a donation from the details a wallet returns', function () {
    mockIntent();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 150)
        ->set('coverFee', false)
        ->set('frequency', 'one_time')
        ->call('submitExpress', 'Nur Aisyah binti Rahman', 'aisyah@example.com');

    $donation = Donation::query()->sole();
    $donor = Donor::query()->sole();

    expect((float) $donation->gross_amount)->toBe(150.0)
        ->and($donation->status)->toBe(DonationStatus::Pending)
        ->and($donation->type)->toBe(DonationType::OneTime)
        ->and($donor->email)->toBe('aisyah@example.com')
        ->and($donor->first_name)->toBe('Nur')
        ->and($donor->last_name)->toBe('Aisyah binti Rahman');
});

it('keeps a single-word name as the first name', function () {
    mockIntent();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 50)
        ->set('coverFee', false)
        ->set('frequency', 'one_time')
        ->call('submitExpress', 'Ahmad', 'ahmad@example.com');

    // An empty surname, the same as a donor who leaves the field blank on the
    // form rather than a special case for wallets.
    expect(Donor::query()->sole())
        ->first_name->toBe('Ahmad')
        ->last_name->toBe('');
});

it('still charges the fee cover the donor agreed to', function () {
    mockIntent();

    $component = Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 100)
        ->set('coverFee', true)
        ->set('frequency', 'one_time');

    $expectedCover = $component->get('estimatedFee');

    $component->call('submitExpress', 'Siti Hawa', 'siti@example.com');

    expect((float) Donation::query()->sole()->donor_fee_covered)->toBe($expectedCover);
});

it('refuses a monthly donation, which needs a stored mandate', function () {
    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 50)
        ->set('frequency', 'monthly')
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com');
})->throws(RuntimeException::class, 'Express checkout is not available');

it('refuses a CHIP campaign, which redirects instead', function () {
    $this->organization->update(['chip_brand_id' => 'BRAND', 'chip_api_key' => 'secret', 'chip_enabled' => true]);
    $this->campaign->update(['payment_gateway' => PaymentGateway::Chip]);

    Livewire::test(DonationForm::class, ['element' => $this->element->fresh()])
        ->set('amount', 50)
        ->set('frequency', 'one_time')
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com');
})->throws(RuntimeException::class, 'Express checkout is not available');

it('rejects an address the wallet could not give us', function () {
    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 50)
        ->set('coverFee', false)
        ->set('frequency', 'one_time')
        ->call('submitExpress', 'Ahmad Donor', 'not-an-email')
        ->assertHasErrors('email');

    expect(Donation::query()->count())->toBe(0);
});

it('reuses the pending attempt when a wallet payment is retried', function () {
    mockIntent();
    fakeExpressIntentLookup();

    $component = Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 75)
        ->set('coverFee', false)
        ->set('frequency', 'one_time');

    $component->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com');
    $component->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com');

    expect(Donation::query()->count())->toBe(1);
});

it('offers the buttons only for one-off card donations', function () {
    $component = Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('frequency', 'one_time');

    expect($component->instance()->expressCheckoutAvailable())->toBeTrue();

    $component->set('frequency', 'monthly');

    expect($component->instance()->expressCheckoutAvailable())->toBeFalse();
});

it('keeps the wallet mount point in the layout so Stripe can measure the device', function () {
    // Stripe cannot work out what a device offers inside a hidden element, and
    // it renders nothing when there is no wallet, so an always-present
    // container costs no space.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('id="express-checkout-element"', false)
        ->assertSee('mountExpressCheckout', false)
        ->assertSee("buttonType: { applePay: 'donate', googlePay: 'donate' }", false);
});

it('quotes the wallet the same total the donor sees', function () {
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        // expressAmountInCents adds the fee cover when the donor opted in.
        ->assertSee('expressAmountInCents()', false)
        ->assertSee("this.\$watch('coverFee', () => this.syncExpressAmount())", false);
});

it('offers the same methods the payment step does', function () {
    // Link is switched off on the payment step; the two steps must not
    // disagree about what a donor can use.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee("paymentMethods: { link: 'never' }", false);
});

it('reads the wallet list under the name Stripe actually sends', function () {
    // The event carries `paymentMethods`; reading anything else leaves the
    // divider hidden while the buttons render, which looked like a layout bug.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee("availablepaymentmethodschange', ({ paymentMethods })", false)
        ->assertDontSee('availablePaymentMethods', false);
});

it('names the card path once a wallet button sits above it', function () {
    // "Continue" stops saying where it leads once there is a wallet button to
    // contrast against, so the label switches to name the card path.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('Donate with card', false)
        ->assertSee("x-show=\"! (expressAvailable && frequency === 'one_time')\"", false);
});

it('leaves an organiser who set their own button text alone', function () {
    $this->element->update(['config' => [
        'template' => 'secure-donation',
        'button_text' => 'Sumbang Sekarang',
    ]]);

    // The organiser's own wording only applies to the embedded form.
    $response = $this->get(route('donations.show', $this->element).'?embed=1')->assertOk();

    expect($response->getContent())
        ->toContain('Sumbang Sekarang')
        ->not->toContain('Donate with card');
});
