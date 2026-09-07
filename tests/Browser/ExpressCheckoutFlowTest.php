<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

/**
 * The wallet buttons live entirely in Alpine and Stripe.js, so rendered-markup
 * assertions can only prove the container exists - not what happens once the
 * donor touches anything. Every fault this feature shipped with was found by
 * eye rather than by a test: a Link button that should have been switched off,
 * a divider that never appeared, and a button that stayed on screen for monthly
 * gifts and raised a 500 when tapped. These drive the real page instead.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr']],
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'status' => CampaignStatus::Active,
        'title' => 'Wallet flow',
    ]);
    $this->element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'token' => 'walletflow',
        'type' => ElementType::Popup,
        'config' => [
            'template' => 'secure-donation',
            'allow_monthly' => true,
            'allow_cover_fee' => true,
            'default_frequency' => 'one_time',
            'default_amount' => 100,
        ],
    ]);

    $this->url = '/donate/'.$this->element->token.'?popup=1';
});

/**
 * Read a value out of the checkout's Alpine state.
 */
function checkoutState(string $expression): string
{
    return <<<JS
    (() => {
        const root = [...document.querySelectorAll('[x-data]')]
            .find(el => el._x_dataStack?.[0] && 'expressAvailable' in el._x_dataStack[0]);
        const state = root._x_dataStack[0];
        return String({$expression});
    })()
    JS;
}

it('keeps the wallet available for a monthly gift', function () {
    // Campaigns that open on Monthly - and several do - would otherwise never
    // show a wallet button at all.
    $page = visit($this->url);

    $page->assertVisible('#express-checkout-wrapper')
        ->click('[data-frequency="monthly"]')
        ->assertVisible('#express-checkout-wrapper')
        ->assertScript(checkoutState('state.frequency'), 'monthly');
});

it('rebuilds the wallet element when the donor changes frequency', function () {
    // The subscription terms are fixed when the element is created, so the old
    // one has to be torn down rather than updated.
    $page = visit($this->url);

    $page->click('[data-frequency="monthly"]')
        ->assertScript(checkoutState('state.frequency'), 'monthly')
        ->click('[data-frequency="one_time"]')
        ->assertScript(checkoutState('state.frequency'), 'one_time');
});

it('quotes the wallet the donation plus the cover the donor agreed to', function () {
    $page = visit($this->url);

    // RM100 with the fee cover on: the wallet must be told the full total.
    $page->assertScript(checkoutState('state.expressAmountInCents() > 10000'), 'true');
});

it('loads the checkout without a javascript error', function () {
    visit($this->url)->assertNoJavascriptErrors();
});

it('does not offer the wallet on a modal that opens past the amount step', function () {
    // The amount is settled by then, the container is hidden, and Stripe cannot
    // measure a hidden element.
    visit('/donate/'.$this->element->token.'?popup=1&step=2')
        ->assertMissing('#express-checkout-wrapper');
});

it('knows the smallest amount Stripe will charge in this currency', function () {
    // RM0.50 is not a chargeable amount; the old floor of 50 cents was borrowed
    // from dollars and quietly produced one.
    visit($this->url)
        ->assertScript(checkoutState('state.expressMinimumInCents()'), '200');
});

it('does not quote the wallet an amount Stripe would refuse', function () {
    // A donor typing their own amount passes through an empty field, and handing
    // that to a mounted element dropped the wallet buttons for good - they never
    // came back once a real amount was typed.
    $page = visit($this->url);

    $page->assertScript(checkoutState('state.expressAmountIsChargeable()'), 'true')
        ->assertScript(checkoutState("(state.amount = '', state.expressAmountIsChargeable())"), 'false')
        ->assertScript(checkoutState("(state.amount = '10', state.expressAmountIsChargeable())"), 'true');
});

/**
 * An organisation that takes more than ringgit. Switching currency changes the
 * amount Stripe is quoted, the floor it will refuse below, and the fee the
 * donor is offered to cover - so the wallet has to survive the switch.
 */
function multiCurrencyCheckoutUrl(): string
{
    test()->organization->update(['settings' => ['accepted_currencies' => ['myr', 'usd', 'sgd']]]);

    return '/donate/'.test()->element->token.'?popup=1';
}

it('keeps the wallet when the donor switches currency', function () {
    $page = visit(multiCurrencyCheckoutUrl());

    $page->assertScript(checkoutState('state.currency'), 'myr')
        ->click('[data-currency-trigger]')
        ->click('[data-currency="usd"]')
        ->assertScript(checkoutState('state.currency'), 'usd')
        ->assertScript(checkoutState('state.expressAmountIsChargeable()'), 'true');
});

it('uses the floor of the currency the donor switched to', function () {
    // Ringgit refuses below RM2; dollars refuse below 50 cents. Reading the
    // ringgit floor after a switch to dollars would reject valid amounts.
    $page = visit(multiCurrencyCheckoutUrl());

    $page->assertScript(checkoutState('state.expressMinimumInCents()'), '200')
        ->click('[data-currency-trigger]')
        ->click('[data-currency="sgd"]')
        ->assertScript(checkoutState('state.expressMinimumInCents()'), '50');
});

it('quotes the wallet the donation alone when the donor declines the fee', function () {
    // With the cover off the wallet must be told the donation and nothing more,
    // or the donor is charged for a fee they chose not to pay.
    $page = visit($this->url);

    $page->assertScript(checkoutState("(state.amount = '100', state.coverFee = false, state.expressAmountInCents())"), '10000')
        ->assertScript(checkoutState('(state.coverFee = true, state.expressAmountInCents() > 10000)'), 'true');
});

it('sends the wallet the same amount the donor is shown', function () {
    // The two are computed separately, so a change to either could silently
    // charge something other than the total on screen.
    $page = visit($this->url);

    $page->assertScript(
        checkoutState("(state.amount = '50', state.coverFee = true, state.expressAmountInCents() === Math.round((50 + parseFloat(state.estimatedFeeAmount)) * 100))"),
        'true'
    );
});
