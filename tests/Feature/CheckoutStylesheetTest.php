<?php

use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Support\Facades\Vite;

/**
 * The checkout builds its stylesheet from an explicit `@source` list rather
 * than scanning the whole application, so a view added to the checkout but
 * missing from that list loses its styling with no build error to show for
 * it. These tests render the checkout and check every class it emits against
 * the built stylesheet.
 *
 * They read rendered HTML, so a class that only ever appears in a Blade
 * expression for a state this render does not reach is not covered here -
 * add a case that reaches that state.
 */
function builtStylesheet(string $entry): string
{
    $manifest = json_decode(
        file_get_contents(public_path('build/manifest.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    return file_get_contents(public_path('build/'.$manifest[$entry]['file']));
}

/**
 * Tailwind escapes every character outside [A-Za-z0-9_-] when it writes a
 * class selector, so `text-3xl/none` is emitted as `.text-3xl\/none`.
 */
function escapedSelector(string $token): string
{
    return '.'.preg_replace('/[^A-Za-z0-9_-]/', '\\\\$0', $token);
}

/**
 * @return list<string>
 */
function classTokensIn(string $html): array
{
    preg_match_all('/class="([^"]*)"/', $html, $matches);

    $tokens = [];

    foreach ($matches[1] as $attribute) {
        foreach (preg_split('/\s+/', $attribute, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            // The match also picks up x-bind:class, which is worth scanning -
            // it holds classes for states this render never reaches - but its
            // ternaries leave behind punctuation that is no class at all.
            if (preg_match('/[A-Za-z]/', $token) !== 1) {
                continue;
            }

            $tokens[$token] = true;
        }
    }

    return array_keys($tokens);
}

function styledCheckoutElement(): Element
{
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();

    return Element::factory()->for($organization)->for($campaign)->create([
        'type' => ElementType::Form,
    ]);
}

it('styles every class the checkout renders', function (string $mode) {
    $checkoutCss = builtStylesheet('resources/css/checkout.css');
    // The full stylesheet tells us which tokens are utilities at all: a name
    // absent from both is a plain CSS class, not a rule the scan dropped.
    $appCss = builtStylesheet('resources/css/app.css');

    $html = $this->get(route('donations.show', styledCheckoutElement()->token).$mode)
        ->assertOk()
        ->getContent();

    $unstyled = [];

    foreach (classTokensIn($html) as $token) {
        $selector = escapedSelector($token);

        if (str_contains($appCss, $selector) && ! str_contains($checkoutCss, $selector)) {
            $unstyled[] = $token;
        }
    }

    expect($unstyled)->toBe([]);
})->with([
    'popup' => '?popup=1',
    'embed' => '?embed=1',
    'standalone' => '',
]);

it('leaves the checkout stylesheet far smaller than the application one', function () {
    $checkout = strlen(builtStylesheet('resources/css/checkout.css'));
    $app = strlen(builtStylesheet('resources/css/app.css'));

    // It was the full 343 KB sheet before it had its own entry.
    expect($checkout)->toBeLessThan($app / 4);
});

it('keeps Flux out of the checkout, which renders no Flux components', function () {
    $checkoutCss = builtStylesheet('resources/css/checkout.css');

    expect($checkoutCss)->not->toContain('data-flux-field');
});

it('serves the checkout stylesheet to every checkout mode', function (string $mode) {
    $checkoutAsset = Vite::asset('resources/css/checkout.css');

    $this->get(route('donations.show', styledCheckoutElement()->token).$mode)
        ->assertOk()
        ->assertSee($checkoutAsset, false)
        ->assertDontSee(Vite::asset('resources/css/app.css'), false);
})->with([
    'popup' => '?popup=1',
    'embed' => '?embed=1',
    'standalone' => '',
]);
