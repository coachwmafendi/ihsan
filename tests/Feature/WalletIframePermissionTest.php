<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Http\Controllers\EmbedCheckoutController;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The checkout modal is a cross-origin iframe: app.getihsan.my inside the
 * organisation's own site. Safari refuses Apple Pay in a cross-origin iframe
 * unless it carries allow="payment", so every place that builds the frame has
 * to set it or the wallet silently never appears.
 *
 * These go through the routes an organisation's site actually loads rather than
 * naming files, because an earlier hand-written list of files missed the one
 * embed script the controller builds inline - and that was the copy still
 * serving sites in production.
 */
it('sets payment permission on every frame an embed script builds', function (string $route) {
    $script = $this->get($route)->assertOk()->getContent();

    $frames = preg_match_all('/<iframe[^>]*>|createElement\(["\']iframe["\']\)/i', $script);

    expect($frames)->toBeGreaterThan(0, "No iframe found in {$route}; this test is no longer checking anything.");

    // Either spelling counts: markup attribute, or setAttribute on a built node.
    expect(substr_count($script, 'payment *'))->toBeGreaterThanOrEqual($frames);
})->with([
    '/embed.js',
    '/e/loader.js',
    '/e/widget.js',
]);

it('sets the permission on the modal frame the copy-paste snippet builds', function () {
    $snippet = file_get_contents(base_path('resources/views/filament/forms/components/element-embed-snippet.blade.php'));

    expect($snippet)->toContain('data-ihsan-frame allow="payment *"');
});

it('serves a button embed whose opener grants payment permission', function () {
    // The button sits in its own frame and asks the parent to open the modal,
    // so the permission has to be on the modal the parent builds.
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Active]);
    $element = Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Popup,
        'is_active' => true,
    ]);

    $this->get('/e/button/'.$element->token)
        ->assertOk();

    expect($this->get('/embed.js')->getContent())->toContain('payment *');
});

it('takes the frame permission from one place', function () {
    // Each script carried its own copy of the string, and one of them had lost
    // the payment permission. The controller substitutes a single constant when
    // it serves them, so a change reaches all three or none.
    // The constant went public when the donor portal's own modal needed the
    // same permission; what this test is about is that there is one of it, not
    // who is allowed to read it.
    $controller = file_get_contents(base_path('app/Http/Controllers/EmbedCheckoutController.php'));

    expect($controller)->toContain("const IframeAllow = 'payment *; clipboard-write; autoplay';");
    expect(EmbedCheckoutController::IframeAllow)->toBe('payment *; clipboard-write; autoplay');

    foreach (['resources/js/widget.js', 'resources/js/loader.js'] as $script) {
        expect(file_get_contents(base_path($script)))
            ->toContain('IHSAN_IFRAME_ALLOW')
            ->not->toContain('payment *; clipboard-write; autoplay');
    }
});

it('leaves no placeholder in what it actually serves', function (string $route) {
    // A substitution that silently failed would hand the browser the literal
    // token, and the frame would carry no permission at all.
    $script = $this->get($route)->assertOk()->getContent();

    expect($script)
        ->not->toContain('IHSAN_IFRAME_ALLOW')
        ->toContain('payment *');
})->with([
    '/e/widget.js',
    '/e/loader.js',
    '/embed.js',
]);

it('closes the checkout modal on Escape, whichever embed opened it', function () {
    // The loader's modal closed on Escape and the widget's did not, so the same
    // donor met two different behaviours depending on which script the site had.
    foreach (['resources/js/widget.js', 'resources/js/loader.js'] as $script) {
        expect(file_get_contents(base_path($script)))->toContain('Escape');
    }
});
