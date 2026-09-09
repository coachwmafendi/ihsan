<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

/**
 * A monthly donor paying by wallet always sees the terms, because Apple puts
 * them on its own sheet and will not open without them. The card path showed
 * nothing at all - the same commitment, disclosed to one donor and not the
 * other, on the same screen.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr']],
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'status' => CampaignStatus::Active,
        'allow_recurring' => true,
    ]);
    $this->element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'token' => 'terms',
        'type' => ElementType::Popup,
        'config' => ['template' => 'secure-donation', 'allow_monthly' => true, 'default_amount' => 100],
    ]);
});

function termsOnPaymentStep(string $frequency): string
{
    return <<<JS
    (async () => {
        const root = [...document.querySelectorAll('[x-data]')]
            .find(el => el._x_dataStack?.[0] && 'expressAvailable' in el._x_dataStack[0]);
        const state = root._x_dataStack[0];

        state.selectFrequency('{$frequency}');
        state.setAmount(100);
        state.donorFirstName = 'Aminah';
        state.donorEmail = 'aminah@example.test';
        state.currentStep = 3;

        await new Promise(r => setTimeout(r, 600));

        const line = [...document.querySelectorAll('p')]
            .find(p => /every month from/.test(p.textContent) && p.offsetParent !== null);

        if (!line) return 'none';

        const text = line.textContent.replace(/\s+/g, ' ').trim();

        // The figures move with the fee rate and the clock, so check the shape:
        // a total, a date, and the way out.
        return [
            /^RM \d+\.\d{2} today, then every month from \d{1,2} [A-Z][a-z]{2}\./.test(text),
            /Cancel any time — the link is in your receipt email\.$/.test(text),
        ].join(',');
    })()
    JS;
}

it('tells a monthly donor what they are agreeing to, where they agree to it', function () {
    // Total, next charge date, and how to stop - the three things Apple forces
    // onto its own sheet and the card path never said.
    visit('/donate/terms?popup=1')->assertScript(termsOnPaymentStep('monthly'), 'true,true');
});

it('says nothing of the sort to a one-time donor', function () {
    visit('/donate/terms?popup=1')->assertScript(termsOnPaymentStep('one_time'), 'none');
});
