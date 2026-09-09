<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

/**
 * The help row is three buttons and one panel, and the panel closing itself was
 * invisible to every assertion that only read the markup: the links are
 * siblings of the panel, so an outside-click guard hung on the panel counted a
 * tap on them as outside. The first tap of all worked - nothing was shown yet,
 * so nothing was listening - and every tap after it opened and shut in the same
 * gesture.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr']],
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'status' => CampaignStatus::Active,
        'title' => 'Help row',
    ]);
    $this->element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'token' => 'helprow',
        'type' => ElementType::Popup,
        'config' => ['template' => 'secure-donation', 'allow_monthly' => true, 'default_amount' => 100],
    ]);

    $this->url = '/donate/'.$this->element->token.'?popup=1';
});

/**
 * Alpine registers the outside-click guard only once the panel is on screen, a
 * tick after the state changes - so a sequence read synchronously misses the
 * very thing that was wrong. Each tap gets a frame to settle.
 */
function helpRowTaps(string $taps): string
{
    return <<<JS
    (async () => {
        const panel = document.getElementById('checkout-help-panel');
        const row = panel.closest('[x-data]');
        const state = row._x_dataStack[0];
        const links = [...row.querySelectorAll('button')]
            .filter(b => /secure|cancel a monthly|Report a problem/i.test(b.textContent));
        const settle = () => new Promise(r => setTimeout(r, 250));
        const seen = [];

        await settle();

        for (const tap of [{$taps}]) {
            (tap === 'body' ? document.body : links[tap]).click();
            await settle();
            seen.push(String(state.open));
        }

        return seen.join(',');
    })()
    JS;
}

it('keeps answering when the donor moves from one question to the next', function () {
    // Every tap after the first used to open and shut in the same gesture.
    visit($this->url)->assertScript(helpRowTaps('0, 1, 2, 0'), 'secure,cancel,problem,secure');
});

it('still closes when the donor taps the same question again', function () {
    visit($this->url)->assertScript(helpRowTaps('0, 0'), 'secure,null');
});

it('still closes when the donor taps somewhere else entirely', function () {
    visit($this->url)->assertScript(helpRowTaps("0, 'body'"), 'secure,null');
});
