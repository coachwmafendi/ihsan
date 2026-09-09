<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;

/**
 * Every validation message in the checkout was a plain div. A donor using a
 * screen reader pressed Continue and heard nothing at all - the form simply
 * stopped, with no way to know why or which field was at fault.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'settings' => ['accepted_currencies' => ['myr']],
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'status' => CampaignStatus::Active,
        'minimum_amount' => 10,
        'title' => 'Error announcement',
    ]);
    $this->element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'token' => 'errorsay',
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

function checkoutScript(string $expression): string
{
    return <<<JS
    (() => {
        const root = [...document.querySelectorAll('[x-data]')]
            .find(el => el._x_dataStack?.[0] && 'expressAvailable' in el._x_dataStack[0]);
        const state = root._x_dataStack[0];
        const amountInput = document.querySelector('[x-ref="amountInput"]');
        return String({$expression});
    })()
    JS;
}

it('leaves a valid field unmarked', function () {
    visit($this->url)
        ->assertScript(checkoutScript("amountInput.getAttribute('aria-invalid')"), 'false')
        ->assertScript(checkoutScript("amountInput.getAttribute('aria-describedby')"), 'donation-amount-error');
});

it('announces a rejected amount and marks the field invalid', function () {
    $page = visit($this->url);

    // Below the campaign's RM10 minimum, which is what step one refuses.
    $page->assertScript(checkoutScript("(state.amount = '1', state.validateStep1())"), 'false')
        ->assertScript(checkoutScript("document.getElementById('donation-amount-error').getAttribute('role')"), 'alert')
        ->assertScript(checkoutScript("document.getElementById('donation-amount-error').textContent.trim()"), 'Minimum amount is RM 10.')
        ->assertScript(checkoutScript("amountInput.getAttribute('aria-invalid')"), 'true');
});

it('puts the cursor on the field that failed', function () {
    // Hearing what is wrong is half of it; the donor still has to reach the
    // field, and on a phone the message can sit off screen entirely.
    $page = visit($this->url);

    $page->assertScript(checkoutScript("(state.amount = '1', state.nextStep(), true)"), 'true')
        ->assertScript(checkoutScript('document.activeElement === amountInput'), 'true');
});
