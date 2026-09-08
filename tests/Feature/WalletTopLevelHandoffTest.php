<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Apple validates Apple Pay against the top-level domain, so a form embedded on
 * a site Stripe never registered gets no wallet at all. Some organisations
 * cannot fix that - the site belongs to a platform they only rent a subdomain
 * on, with no way to host the verification file. Rather than leave them with no
 * wallet, the checkout offers to open as a full page on our own domain.
 */
beforeEach(function () {
    config()->set('app.app_panel_domain', 'app.getihsan.my');

    $this->organization = Organization::factory()->stripeConnected()->create([
        'settings' => ['allowed_domains' => ['tahfizannur.org']],
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'status' => CampaignStatus::Active,
    ]);
    $this->element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Form,
        // No button_text: an organiser's own wording wins over ours, and these
        // tests are about the label we choose when they have not set one.
        'config' => ['template' => 'secure-donation'],
    ]);
});

function embeddedOn(string $pageUrl): Testable
{
    return Livewire::withQueryParams(['embed' => '1', 'pu' => $pageUrl])
        ->test(DonationForm::class, ['element' => test()->element]);
}

it('keeps the wallet in the frame on a site that is registered', function () {
    expect(embeddedOn('https://tahfizannur.org/derma')->instance()->walletRequiresTopLevel())
        ->toBeFalse();
});

it('hands the wallet off on a subdomain nobody registered', function () {
    // onpay.my being registered is no help: Stripe registers domains exactly.
    expect(embeddedOn('https://mtaqlaa.onpay.my/order/form/infaq')->instance()->walletRequiresTopLevel())
        ->toBeTrue();
});

it('hands the wallet off on a domain Stripe refused to register', function () {
    $this->organization->update(['settings' => [
        'allowed_domains' => ['mtaqlaa.onpay.my'],
        'payment_domain_errors' => ['mtaqlaa.onpay.my' => 'The domain could not be verified.'],
    ]]);

    expect(embeddedOn('https://mtaqlaa.onpay.my/order/form/infaq')->instance()->walletRequiresTopLevel())
        ->toBeTrue();
});

it('leaves the wallet alone when the checkout is not embedded at all', function () {
    expect(Livewire::test(DonationForm::class, ['element' => $this->element])->instance()->walletRequiresTopLevel())
        ->toBeFalse();
});

it('does not hand off our own checkout domain to itself', function () {
    expect(embeddedOn('https://app.getihsan.my/donate/abc')->instance()->walletRequiresTopLevel())
        ->toBeFalse();
});

it('offers the handoff button instead of a wallet element that cannot mount', function () {
    $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://mtaqlaa.onpay.my/x'))
        ->assertOk()
        ->assertSee('Pay with Apple Pay or Google Pay')
        ->assertSee('target="_top"', false)
        ->assertSee('x-bind:href="topLevelCheckoutHref()"', false)
        ->assertDontSee('id="express-checkout-element"', false);
});

it('still mounts the wallet element on a registered site', function () {
    $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://tahfizannur.org/derma'))
        ->assertOk()
        ->assertSee('id="express-checkout-element"', false)
        ->assertDontSee('Pay with Apple Pay or Google Pay');
});

it('carries the amount and frequency across so the donor does not start over', function () {
    $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://mtaqlaa.onpay.my/x'))
        ->assertOk()
        ->assertSee('cover_fee:', false)
        ->assertSee('frequency: this.frequency', false)
        ->assertSee('amount: this.amount', false);
});

it('points the handoff at the hosted checkout for this element', function () {
    expect(embeddedOn('https://mtaqlaa.onpay.my/x')->instance()->topLevelCheckoutUrl())
        ->toContain('/donate/'.$this->element->token);
});

it('does not need the embedding site to do anything', function () {
    // The first version asked the host page to navigate for us, which meant an
    // older cached copy of widget.js sitting on the site silently broke the
    // button. A link with target="_top" navigates on its own.
    $widget = file_get_contents(base_path('resources/js/widget.js'));

    expect($widget)->not->toContain('ihsan:open-checkout');

    $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://mtaqlaa.onpay.my/x'))
        ->assertOk()
        ->assertSee('target="_top"', false);
});

it('names the card path when the handoff button sits above it', function () {
    // expressAvailable never turns true on this path - no Stripe element is
    // mounted - so the label would otherwise stay "Continue" while a wallet
    // button sat right above it.
    $response = $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://mtaqlaa.onpay.my/x'))
        ->assertOk();

    expect($response->getContent())
        ->toContain('Donate with card')
        ->not->toContain('x-show="! expressAvailable"');
});

it('keeps the label tied to the wallet on a site that mounts one', function () {
    $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://tahfizannur.org/derma'))
        ->assertOk()
        ->assertSee('x-show="! expressAvailable"', false)
        ->assertSee('Donate with card');
});

it('trusts what Stripe last said over what the organiser typed in the list', function () {
    // A domain sitting in the list proves only that someone typed it. Keying
    // off the list alone let a domain switch the fallback off without
    // switching any wallet on - the state this page was in on 7 Sep.
    $this->organization->update(['settings' => [
        'allowed_domains' => ['mtaqlaa.onpay.my'],
        'wallet_verified_domains' => ['tahfizannur.org'],
    ]]);

    expect(embeddedOn('https://mtaqlaa.onpay.my/x')->instance()->walletRequiresTopLevel())
        ->toBeTrue();
});

it('leaves the wallet in the frame on a domain Stripe reports as verified', function () {
    $this->organization->update(['settings' => [
        'allowed_domains' => [],
        'wallet_verified_domains' => ['tahfizannur.org'],
    ]]);

    expect(embeddedOn('https://tahfizannur.org/derma')->instance()->walletRequiresTopLevel())
        ->toBeFalse();
});

it('carries the page the donor came from through the handoff', function () {
    // The handoff leaves the organisation's site, and a donation that records
    // our own checkout as its source tells every ad platform the wrong thing.
    // A real handoff donation was filed against app.getihsan.my this way.
    $url = embeddedOn('https://mtaqlaa.onpay.my/order/form/infaq')
        ->instance()
        ->topLevelCheckoutUrl();

    expect($url)->toContain('pu='.urlencode('https://mtaqlaa.onpay.my/order/form/infaq'));
});

it('leaves the handoff url clean when there is no host page to record', function () {
    expect(Livewire::test(DonationForm::class, ['element' => $this->element])->instance()->topLevelCheckoutUrl())
        ->not->toContain('pu=');
});

it('appends the donor choices to a handoff url that already carries the host page', function () {
    // Two query strings joined with a second "?" would drop everything after it.
    $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://mtaqlaa.onpay.my/x'))
        ->assertOk()
        ->assertSee("this.topLevelCheckoutUrl.includes('?') ? '&' : '?'", false);
});

it('does not hand off from the page the handoff already landed on', function () {
    // The hosted checkout carries the site the donor came from so attribution
    // stays right, and reading that alone made this page offer the handoff
    // button again - a loop the donor could never get out of.
    $this->get(route('donations.show', $this->element).'?amount=10&frequency=one_time&currency=myr&cover_fee=1&pu='.urlencode('https://mtaqlaa.onpay.my/x'))
        ->assertOk()
        ->assertSee('id="express-checkout-element"', false)
        ->assertDontSee('Pay with Apple Pay or Google Pay');
});

it('still hands off from inside an embedded frame', function () {
    expect(embeddedOn('https://mtaqlaa.onpay.my/x')->instance()->walletRequiresTopLevel())
        ->toBeTrue();
});

it('will not hand off a donor who has not entered an amount', function () {
    // The handoff is a link, so it never met the checks the card button runs.
    // An emptied amount field sailed straight through to the hosted page.
    $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://mtaqlaa.onpay.my/x'))
        ->assertOk()
        ->assertSee('if (! validateStep1()) $event.preventDefault()', false)
        ->assertSee('x-bind:aria-disabled="! amountIsUsable()"', false);
});

it('shows the same minimum message the card button would', function () {
    $this->get(route('donations.show', $this->element).'?embed=1&pu='.urlencode('https://mtaqlaa.onpay.my/x'))
        ->assertOk()
        ->assertSee('x-show="stepErrors.amount"', false);
});
