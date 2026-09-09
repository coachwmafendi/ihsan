<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Livewire\DonationForm;
use App\Mail\CheckoutProblemReport;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * A donor deciding whether to hand over a card has questions, and one of them
 * is what happens next month. Until now the checkout answered none of them, and
 * someone whose payment broke had no way to tell anyone at all.
 */
beforeEach(function () {
    RateLimiter::clear('checkout-problem:127.0.0.1');

    $this->organization = Organization::factory()->create([
        'name' => 'Tahfiz Al Ayubi',
        'contact_email' => 'admin@alayubi.test',
    ]);
    $this->campaign = Campaign::factory()->for($this->organization)->create([
        'status' => CampaignStatus::Active,
        'title' => 'Building fund',
    ]);
    $this->element = Element::factory()->for($this->organization)->for($this->campaign)->create([
        'type' => ElementType::Popup,
        'config' => ['template' => 'secure-donation'],
    ]);
});

it('answers the three questions a donor asks before paying', function () {
    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->assertSee('Is my donation secure?')
        ->assertSee('Can I cancel a monthly donation?')
        ->assertSee('Report a problem')
        // The monthly answer has to say when the money moves, not only that it
        // can be stopped.
        ->assertSee('charged on the same date each month');
});

it('sends a checkout problem to the organisation it happened to', function () {
    Mail::fake();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('problemReport', 'The card form never loaded on step three.')
        ->set('problemReportConfirmed', true)
        ->call('submitProblemReport')
        ->assertHasNoErrors()
        ->assertSet('problemReportSent', true)
        ->assertSet('problemReport', '');

    Mail::assertQueued(CheckoutProblemReport::class, function (CheckoutProblemReport $mail) {
        return $mail->hasTo('admin@alayubi.test')
            // Most of what breaks in a checkout is ours, and an organisation
            // reading about a wallet that would not open can do nothing with it.
            && $mail->hasBcc(support_email())
            && $mail->campaign->is($this->campaign)
            && str_contains($mail->reportMessage, 'never loaded');
    });
});

it('will not send a report without the confirmation that it holds no card details', function () {
    // The person most likely to paste a card number is the one describing a
    // payment that just failed.
    Mail::fake();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('problemReport', 'My card was declined, the number is')
        ->set('problemReportConfirmed', false)
        ->call('submitProblemReport')
        ->assertHasErrors(['problemReportConfirmed'])
        ->assertSet('problemReportSent', false);

    Mail::assertNothingQueued();
});

it('refuses a report too short to act on', function () {
    Mail::fake();

    Livewire::test(DonationForm::class, ['element' => $this->element])
        ->set('problemReport', 'broken')
        ->set('problemReportConfirmed', true)
        ->call('submitProblemReport')
        ->assertHasErrors(['problemReport']);

    Mail::assertNothingQueued();
});

it('stops a flood from one address', function () {
    // No account stands behind this form, so the address it came from is the
    // only thing holding it back.
    Mail::fake();

    $component = Livewire::test(DonationForm::class, ['element' => $this->element]);

    foreach (range(1, 3) as $attempt) {
        $component->set('problemReport', 'Attempt number '.$attempt.' at describing this.')
            ->set('problemReportConfirmed', true)
            ->call('submitProblemReport')
            ->assertHasNoErrors();
    }

    $component->set('problemReport', 'One more time, with feeling.')
        ->set('problemReportConfirmed', true)
        ->call('submitProblemReport')
        ->assertHasErrors(['problemReport']);

    Mail::assertQueuedCount(3);
});

it('falls back to Ihsan support when the organisation left no address', function () {
    Mail::fake();

    $this->organization->update(['contact_email' => null]);

    Livewire::test(DonationForm::class, ['element' => $this->element->fresh()])
        ->set('problemReport', 'Nothing happens when I press donate.')
        ->set('problemReportConfirmed', true)
        ->call('submitProblemReport');

    Mail::assertQueued(CheckoutProblemReport::class, fn (CheckoutProblemReport $mail) => $mail->hasTo(support_email()));
});

it('runs the row under the whole modal rather than the column the form sits in', function () {
    // In the two-column popup the questions belong to the checkout as a whole,
    // and tucked inside the 440px form column the panel never had the width to
    // lift above the row - on a desktop screen it still opened as an accordion.
    $markup = file_get_contents(base_path('resources/views/livewire/donation-form.blade.php'));

    expect($markup)->toContain('<div class="md:grid md:grid-cols-[minmax(0,1fr)_440px]">');

    $gridCloses = strpos($markup, '</div>', strpos($markup, '</section>
        @if ($isPopup)'));
    $helpRow = strpos($markup, '<x-checkout-help');

    expect($helpRow)->toBeGreaterThan($gridCloses);

    // And the panel decides where to open from its own width, not the window's,
    // because the checkout is framed at a width the viewport knows nothing of.
    expect(file_get_contents(base_path('resources/views/components/checkout-help.blade.php')))
        ->toContain('this.$el.offsetWidth')
        ->not->toContain('md:absolute');
});
