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
use Illuminate\Support\Facades\Log;
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

it('rejects an address the wallet could not give us, and says so', function () {
    // The donor has already authorised the payment, so a silent refusal leaves
    // them staring at a generic failure with no idea what to do next.
    $attempt = fn () => Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 50)
        ->set('coverFee', false)
        ->set('frequency', 'one_time')
        ->call('submitExpress', 'Ahmad Donor', 'not-an-email');

    expect($attempt)->toThrow(RuntimeException::class, 'The email field must be a valid email address.');

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

it('charges what the donor chose, not what the page was rendered with', function () {
    // The amount step lives in the browser and the wallet skips the step that
    // sends it. Without the donor's choices travelling with the call, a wallet
    // donation was built from the campaign's defaults - the wrong amount, taken
    // silently, with only a frequency mismatch ever raising an error.
    mockIntent();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 500)
        ->set('frequency', 'one_time')
        ->set('coverFee', true)
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com', null, [
            'amount' => 10,
            'frequency' => 'monthly',
            'currency' => 'myr',
            'coverFee' => false,
        ]);

    $donation = Donation::query()->sole();

    expect((float) $donation->gross_amount)->toBe(10.0)
        ->and($donation->type)->toBe(DonationType::Recurring)
        ->and((float) $donation->donor_fee_covered)->toBe(0.0);
});

it('keeps the rendered choices when the wallet sends none', function () {
    mockIntent();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 75)
        ->set('frequency', 'one_time')
        ->set('coverFee', false)
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com');

    expect((float) Donation::query()->sole()->gross_amount)->toBe(75.0);
});

it('refuses a currency the organisation does not accept', function () {
    // Setting it straight onto the component would skip the check the picker
    // does, and bill an organisation in a currency it never enabled.
    mockIntent();

    $this->organization->update(['settings' => ['accepted_currencies' => ['myr']]]);

    Livewire::test(DonationForm::class, ['element' => $this->element->fresh()])
        ->set('amount', 50)
        ->set('frequency', 'one_time')
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com', null, [
            'amount' => 50,
            'currency' => 'usd',
            'frequency' => 'one_time',
            'coverFee' => false,
        ]);

    expect(Donation::query()->sole()->currency)->toBe('myr');
});

it('greets the wallet donor by name on the thank-you screen', function () {
    // The wallet skips the step that collects a name, and the success screen
    // reads it from the browser rather than the server - so a real donation
    // thanked "Friend" and said the receipt went to nobody.
    $this->get(route('donations.show', $this->element))
        ->assertOk()
        ->assertSee('this.donorFirstName = walletFirstName;', false)
        ->assertSee('this.donorEmail = payerEmail;', false);
});

it('shows a wallet failure the way every other payment error is shown', function () {
    // A declined card is the same class of message as cardError, so it reads at
    // the same size, and it sits under the button that failed rather than below
    // the divider where it looks like the card path's problem.
    $body = $this->get(route('donations.show', $this->element))->assertOk()->getContent();

    expect($body)->toContain('x-show="expressError" x-cloak class="mt-1 text-sm text-red-600"');

    $errorAt = strpos($body, 'x-show="expressError"');
    $dividerAt = strpos($body, 'x-show="expressAvailable" x-cloak class="flex items-center gap-3"');

    expect($errorAt)->toBeLessThan($dividerAt);
});

it('tells the wallet donor why their donation was rejected', function () {
    // Livewire answers a failed validation with nothing, so the checkout could
    // only say "could not start the payment" - after the donor had already
    // authorised it with their fingerprint. A USD attempt died this way with
    // nothing on screen or in the log to say which rule refused it.
    Log::spy();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com', null, [
            'amount' => 0.5,
            'frequency' => 'one_time',
            'currency' => 'myr',
            'coverFee' => false,
        ]);
})->throws(RuntimeException::class);

it('writes the rejected wallet donation to the log', function () {
    Log::spy();

    try {
        Livewire::test(DonationForm::class, ['element' => $this->element])
            ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com', null, [
                'amount' => 0.5,
                'frequency' => 'one_time',
                'currency' => 'myr',
                'coverFee' => false,
            ]);
    } catch (RuntimeException $e) {
        // The message reaching the donor is asserted above.
    }

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $message === 'Wallet donation rejected before it could start'
            && $context['currency'] === 'myr');
});

it('judges a foreign-currency gift by the minimum in that currency', function () {
    // The campaign asks for RM10. A USD 5 gift is more than twice that, and was
    // refused anyway because the ringgit figure was applied to dollars.
    mockIntent();

    $this->campaign->update(['minimum_amount' => 10]);
    $this->organization->update(['settings' => ['accepted_currencies' => ['myr', 'usd']]]);

    Livewire::test(DonationForm::class, ['element' => $this->element->fresh()])
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com', null, [
            'amount' => 5,
            'frequency' => 'one_time',
            'currency' => 'usd',
            'coverFee' => false,
        ]);

    expect(Donation::query()->sole())
        ->currency->toBe('usd')
        ->and((float) Donation::query()->sole()->gross_amount)->toBe(5.0);
});

it('still refuses a foreign gift below the campaign minimum', function () {
    $this->campaign->update(['minimum_amount' => 10]);
    $this->organization->update(['settings' => ['accepted_currencies' => ['myr', 'usd']]]);

    $attempt = fn () => Livewire::test(DonationForm::class, ['element' => $this->element->fresh()])
        ->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com', null, [
            'amount' => 1,
            'frequency' => 'one_time',
            'currency' => 'usd',
            'coverFee' => false,
        ]);

    expect($attempt)->toThrow(RuntimeException::class);
    expect(Donation::query()->count())->toBe(0);
});

it('records when a wallet donation actually went through', function () {
    // Only the CHIP path wrote this down, so every Stripe donation - card and
    // wallet alike - had no record of when it succeeded.
    mockIntent();

    $component = Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('amount', 50)
        ->set('coverFee', false)
        ->set('frequency', 'one_time');

    $component->call('submitExpress', 'Ahmad Donor', 'ahmad@example.com');

    $donation = Donation::query()->sole();

    expect($donation->finalized_at)->toBeNull();

    $donation->update(['stripe_payment_intent_id' => 'pi_express']);

    ApiRequestor::setHttpClient(new class implements ClientInterface
    {
        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
        {
            return [json_encode([
                'id' => 'pi_express',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount' => 5000,
                'currency' => 'myr',
                'latest_charge' => null,
            ]), 200, []];
        }
    });

    $component->call('confirmPayment', 'pi_express');

    expect(Donation::query()->sole()->finalized_at)->not->toBeNull();
});
