<?php

use App\Enums\CampaignStatus;
use App\Livewire\Auth\RegisterOrganization;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

it('serves the embed script as javascript', function () {
    $this->get(route('embed.script'))
        ->assertOk()
        ->assertHeader('content-type', 'application/javascript; charset=UTF-8')
        ->assertSee('IhsanCheckout', false)
        ->assertSee('data-ihsan-form', false)
        ->assertSee('width:min(100%,520px)', false)
        ->assertSee('height:min(94vh,820px)', false)
        ->assertSee('/checkout', false);
});

it('serves the widget script with checkout modal skeleton loading states', function () {
    $this->get(route('widget.script'))
        ->assertOk()
        ->assertHeader('content-type', 'application/javascript')
        ->assertSee('createCheckoutSkeleton', false)
        ->assertSee('preloadCheckoutImage', false)
        ->assertSee('data-ihsan-checkout-skeleton', false)
        ->assertSee('ihsan:donation-ready', false)
        ->assertSee('setTimeout(hideSkeleton, 9000)', false)
        ->assertDontSee('setTimeout(hideSkeleton, 3200)', false)
        ->assertDontSee('setTimeout(hideSkeleton, 450)', false);
});

it('does not render inactive elements from the widget fallback', function () {
    $script = $this->get(route('widget.script'))->getContent();

    expect($script)
        ->toContain('res.status === 404')
        ->toContain('Element not found or inactive')
        ->toContain('if (!data) return;');
});

it('renders element embed snippets from the first-party widget route', function () {
    config(['app.url' => 'https://ihsan.test']);

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'abc123',
        'type' => 'button',
    ]);

    expect(view('filament.forms.components.element-embed-snippet', [
        'element' => $element,
        'liveType' => 'button',
    ])->render())
        ->toContain('https://ihsan.test/e/widget.js')
        ->toContain('?v=')
        ->not->toContain('cdn.jsdelivr');

    expect(view('filament.tables.columns.embed-code-column', [
        'record' => $element,
    ])->render())
        ->toContain('https://ihsan.test/e/widget.js')
        ->toContain('?v=')
        ->not->toContain('cdn.jsdelivr');
});

it('serves the codex embed smoke test page', function () {
    $html = file_get_contents(public_path('codex-embed-test.html'));

    expect($html)
        ->toContain('Ihsan Embed Test')
        ->toContain('https://ihsan.test/embed.js')
        ->toContain('https://ihsan.test/donate/codex-qa-form-token?embed=1');
});

it('redirects an allowed embed checkout request to the hosted donation form', function () {
    $organization = Organization::factory()->create([
        'settings' => ['allowed_domains' => ['mumzatuttaqwa.com']],
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => true,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'form-token-123',
        'is_active' => true,
    ]);

    $this->withHeader('referer', 'https://mumzatuttaqwa.com/ms/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertRedirect(route('donations.show', ['element' => $element->token, 'embed' => 1]));
});

it('rejects embed checkout requests from domains outside the allowlist', function () {
    $organization = Organization::factory()->create([
        'settings' => ['allowed_domains' => ['mumzatuttaqwa.com']],
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => true,
    ]);
    Element::factory()->for($organization)->for($campaign)->create([
        'is_active' => true,
    ]);

    $this->withHeader('referer', 'https://example.com/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertForbidden();
});

it('redirects campaign-direct checkout to the campaign donation form when no element exists', function () {
    $organization = Organization::factory()->create([
        'settings' => ['allowed_domains' => ['mumzatuttaqwa.com']],
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => true,
        'status' => CampaignStatus::Active,
    ]);

    $this->withHeader('referer', 'https://mumzatuttaqwa.com/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertRedirect(route('donations.campaign-show', ['campaign' => $campaign->form_parameter, 'embed' => 1]));
});

it('prefers element checkout over campaign-direct when both exist', function () {
    $organization = Organization::factory()->create([
        'settings' => ['allowed_domains' => ['mumzatuttaqwa.com']],
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => true,
        'status' => CampaignStatus::Active,
    ]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'token' => 'form-token-123',
        'is_active' => true,
    ]);

    $this->withHeader('referer', 'https://mumzatuttaqwa.com/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertRedirect(route('donations.show', ['element' => $element->token, 'embed' => 1]));
});

it('rejects campaign-direct checkout when modal checkout is disabled', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => false,
        'status' => CampaignStatus::Active,
    ]);

    $this->withHeader('referer', 'https://mumzatuttaqwa.com/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertNotFound();
});

it('rejects checkout requests when modal checkout is disabled', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => false,
    ]);
    Element::factory()->for($organization)->for($campaign)->create([
        'is_active' => true,
    ]);

    $this->withHeader('referer', 'https://mumzatuttaqwa.com/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertNotFound();
});

it('rejects embed checkout when org has no allowed domains configured', function () {
    $organization = Organization::factory()->create([
        'settings' => ['allowed_domains' => []],
    ]);
    $campaign = Campaign::factory()->for($organization)->create([
        'form_parameter' => 'RAMADAN2026',
        'checkout_modal_enabled' => true,
        'status' => CampaignStatus::Active,
    ]);
    Element::factory()->for($organization)->for($campaign)->create([
        'is_active' => true,
    ]);

    $this->withHeader('referer', 'https://mumzatuttaqwa.com/?form=RAMADAN2026')
        ->get(route('checkout.form', ['form' => 'RAMADAN2026', 'embed' => 1]))
        ->assertForbidden();
});

it('auto-populates allowed_domains from website_url when org is registered', function () {
    Livewire::test(RegisterOrganization::class)
        ->set('name', 'Test NGO')
        ->set('registration_type', 'ROS')
        ->set('ros_rob_number', 'ROS-TEST-001')
        ->set('sector', 'Education')
        ->set('contact_email', 'admin@testngo.org')
        ->set('website_url', 'https://www.testngo.org')
        ->call('submit');

    $org = Organization::where('ros_rob_number', 'ROS-TEST-001')->firstOrFail();
    expect($org->settings['allowed_domains'])->toBe(['testngo.org']);
});
