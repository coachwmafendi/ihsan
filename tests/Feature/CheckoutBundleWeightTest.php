<?php

use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Support\Facades\Vite;

/**
 * ApexCharts and Chart.js are over three hundred kilobytes gzipped and only
 * the two dashboards draw a chart. While they sat in the shared entry, every
 * checkout iframe downloaded and parsed them before the donor could type an
 * amount, and the skeleton loader covered the wait.
 */
function chartBundleUrl(): string
{
    return Vite::asset('resources/js/charts.js');
}

function checkoutElement(): Element
{
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    return Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);
}

it('keeps the charting libraries out of the checkout modal', function () {
    $element = checkoutElement();

    $response = $this->get(route('donations.show', $element->token).'?popup=1')->assertOk();

    expect($response->getContent())
        ->not->toContain(chartBundleUrl())
        ->toContain(Vite::asset('resources/js/app.js'));
});

it('keeps the charting libraries out of the embedded checkout', function () {
    $element = checkoutElement();

    $response = $this->get(route('donations.show', $element->token).'?embed=1')->assertOk();

    expect($response->getContent())->not->toContain(chartBundleUrl());
});

it('keeps the charting libraries out of the standalone donation page', function () {
    $element = checkoutElement();

    $response = $this->get(route('donations.show', $element->token))->assertOk();

    expect($response->getContent())->not->toContain(chartBundleUrl());
});

it('leaves the shared bundle small enough to stay off the critical path', function () {
    $manifest = json_decode(
        file_get_contents(public_path('build/manifest.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $bundle = public_path('build/'.$manifest['resources/js/app.js']['file']);

    // Was 1.1 MB while it carried the chart libraries.
    expect(filesize($bundle))->toBeLessThan(100 * 1024);
});

it('loads stripe.js without blocking the checkout from rendering', function () {
    $element = checkoutElement();

    $response = $this->get(route('donations.show', $element->token).'?popup=1')->assertOk();

    expect($response->getContent())
        ->toContain('src="https://js.stripe.com/v3/"')
        ->toContain('async')
        ->toContain('window.ihsanStripeJs')
        // A bare synchronous tag stops the parser until Stripe answers.
        ->not->toContain('<script src="https://js.stripe.com/v3/"></script>');
});

it('lets the checkout wait for stripe.js instead of assuming it arrived', function () {
    $element = checkoutElement();

    $response = $this->get(route('donations.show', $element->token).'?popup=1')->assertOk();

    expect($response->getContent())->toContain('await window.ihsanStripeJs');
});

it('reports a failed stripe.js load instead of hanging on the skeleton', function () {
    $element = checkoutElement();

    $response = $this->get(route('donations.show', $element->token).'?popup=1')->assertOk();

    expect($response->getContent())
        ->toContain('onerror="window.ihsanStripeJsSettle.reject')
        ->toContain('Payment system failed to initialize');
});

it('does not ship a font family the checkout never renders', function () {
    // @fonts emits Instrument Sans alone, and nothing asks for it: the sans
    // stack is Inter, which app.css carries. It cost six files and 72 KB.
    $element = checkoutElement();

    foreach (['?popup=1', '?embed=1', ''] as $mode) {
        $content = $this->get(route('donations.show', $element->token).$mode)
            ->assertOk()
            ->getContent();

        expect($content)->not->toContain('instrument-sans');
    }
});
