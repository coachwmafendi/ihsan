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

it('takes a monthly gift from a wallet too', function () {
    mockIntent();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 50)
        ->set('coverFee', false)
        ->set('frequency', 'monthly')
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com');

    expect(Donation::query()->sole())
        ->type->toBe(DonationType::Recurring)
        ->status->toBe(DonationStatus::Pending);
});

it('refuses a CHIP campaign, which redirects instead', function () {
    $this->organization->update(['chip_brand_id' => 'BRAND', 'chip_api_key' => 'secret', 'chip_enabled' => true]);
    $this->campaign->update(['payment_gateway' => PaymentGateway::Chip]);

    Livewire::test(DonationForm::class, ['element' => $this->element->fresh()])
        ->set('amount', 50)
        ->set('frequency', 'one_time')
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com')
        ->assertReturned('');

    expect(Donation::query()->count())->toBe(0);
});

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

it('offers the buttons for both one-off and monthly gifts', function () {
    // Campaigns that open on Monthly - and several do - would otherwise never
    // show a wallet button at all.
    $component = Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('frequency', 'one_time');

    expect($component->instance()->expressCheckoutAvailable())->toBeTrue();

    $component->set('frequency', 'monthly');

    expect($component->instance()->expressCheckoutAvailable())->toBeTrue();
});

it('tells Apple Pay the subscription terms when it opens the sheet', function () {
    // The terms have to arrive in the click handler's resolve(). Declared when
    // the element is created they are ignored, and the sheet does not open at
    // all - the wallet button simply did nothing on a monthly gift.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee("event.expressPaymentType === 'apple_pay'", false)
        ->assertSee('recurringPaymentRequest', false)
        ->assertSee("recurringPaymentIntervalUnit: 'month'", false)
        ->assertSee('event.resolve(options);', false)
        // Must match the PaymentIntent, which saves the card for later.
        ->assertSee("setupFutureUsage: 'off_session'", false);
});

it('does not declare the recurring terms when the element is created', function () {
    // Where they used to live, and where Apple ignores them.
    $body = $this->get(route('donations.show', $this->element))->assertOk()->getContent();

    $create = substr($body, strpos($body, "create('expressCheckout'"), 400);

    expect($create)->not->toContain('recurringPaymentRequest');
});

it('asks Apple for nothing extra on a one-off gift', function () {
    // A one-off donation has no terms to agree to, and sending them would make
    // Apple show a subscription sheet for a single payment.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee("this.frequency === 'monthly' && event.expressPaymentType === 'apple_pay'", false);
});

it('points the donor at somewhere they can cancel', function () {
    $component = Livewire::test(DonationForm::class, ['element' => $this->element]);

    expect($component->instance()->recurringManagementUrl())
        ->toContain('donorportal')
        ->toContain($this->organization->code);
});

it('rebuilds the wallet element when the gift changes frequency', function () {
    // The terms are fixed when the element is created, so switching between
    // one-off and monthly has to start a new one.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee("this.\$watch('frequency', () => this.remountExpressCheckout())", false);
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
        ->assertSee('x-show="! expressAvailable"', false);
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

it('offers the wallet on the inline form, where the amount step actually lives', function () {
    // A Form element shows the amount step inline on the organisation's page and
    // only opens the modal at step 2, so the wallet has to be on the inline one.
    $this->get(route('donations.show', $this->element).'?embed=1')
        ->assertOk()
        ->assertSee('id="express-checkout-element"', false);
});

it('does not mount the wallet on a modal that opens past the amount step', function () {
    // Stripe cannot measure a hidden element; the step-2 modal has the amount
    // settled already.
    $this->get(route('donations.show', $this->element).'?popup=1&step=2')
        ->assertOk()
        ->assertSee('if (this.currentStep !== 1) return;', false);
});

it('asks the wallet for the billing details it needs to record a donor', function () {
    // Stripe only fills billingDetails when the billing address is requested,
    // and the payer's name lives nowhere else. With it off the confirm handler
    // received no name and every wallet payment failed validation.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('billingAddressRequired: true', false)
        ->assertDontSee('billingAddressRequired: false', false);
});

it('reads the payer only from where Stripe actually puts them', function () {
    // payerName/payerEmail belong to the old Payment Request Button; on this
    // element they are always undefined, so a missing name looked like an empty
    // string rather than a fault.
    $body = $this->get(route('donations.show', $this->element))->assertOk()->getContent();

    expect($body)
        ->toContain('event.billingDetails?.name')
        ->not->toContain('event.payerName');
});
