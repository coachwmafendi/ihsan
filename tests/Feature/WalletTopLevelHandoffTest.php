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
        ->assertSee('openTopLevelCheckout()', false)
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

it('only lets our own checkout steer the host page', function () {
    // Any other frame on the page can post a message; without the origin check
    // an embedded ad could redirect the donor away from the site.
    $widget = file_get_contents(base_path('resources/js/widget.js'));

    expect($widget)
        ->toContain('if (event.origin !== baseUrl) return;')
        ->toContain('if (url.indexOf(baseUrl + "/") !== 0) return;');
});
