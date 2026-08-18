<?php

use App\Actions\Stripe\SyncDonorDetailsToStripe;
use App\Enums\UserRole;
use App\Livewire\App\Donations\DonationIndex;
use App\Livewire\App\Donations\DonationShow;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->donor = Donor::factory()->create();
    $this->donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 150.00,
        'net_amount' => 145.50,
        'status' => 'succeeded',
    ]);
});

it('renders the donations index page', function () {
    $response = $this->actingAs($this->user)->get(route('app.donations.index'));

    $response->assertStatus(200);
    $response->assertSee('Donations');
    $response->assertSee($this->donor->name);
    $response->assertSee($this->campaign->title);
});

it('renders the donation show page', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertStatus(200)
        ->assertSee($this->donation->public_id)
        ->assertSee($this->donor->name)
        ->assertSee($this->campaign->title);
});

it('shows the stripe failure reason tooltip on the donation show page', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 150.00,
        'status' => 'failed',
        'stripe_fee_details' => [
            'last_payment_error' => [
                'message' => 'Your card has insufficient funds.',
                'decline_code' => 'insufficient_funds',
            ],
        ],
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $donation])
        ->assertStatus(200)
        ->assertSee('Your card has insufficient funds. (insufficient_funds)');
});

it('shows the pending status tooltip on the donations index page', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'gross_amount' => 150.00,
        'status' => 'pending',
        'stripe_fee_details' => [
            'pending' => [
                'status' => 'requires_action',
                'message' => null,
            ],
        ],
    ]);

    $this->actingAs($this->user)
        ->get(route('app.donations.index'))
        ->assertStatus(200)
        ->assertSee('Awaiting 3D Secure authentication');
});

it('shows the exact original currency amount in a tooltip for non-MYR receipts', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => 'succeeded',
        'currency' => 'usd',
        'gross_amount' => 400.00,
        'base_amount' => 2047.73,
        'net_amount' => 390.00,
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $donation])
        ->assertSee('≈ MYR 2,047.73', false)
        ->assertSee('USD 400.00', false);
});

it('shows the validated badge when the donor email is validated', function () {
    $this->donor->update(['email_validated_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Validated');
});

it('hides the validated badge when the donor email is not validated', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertDontSee('Validated');
});

it('filters donations by period', function () {
    $oldDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'created_at' => now()->subMonths(2),
        'status' => 'succeeded',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['period' => 'this_month']));

    $response->assertStatus(200);
    $response->assertSee($this->donation->public_id);
    $response->assertDontSee($oldDonation->public_id);
});

it('filters donations by status', function () {
    $failedDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => 'failed',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['statusFilter' => 'succeeded']));

    $response->assertStatus(200);
    $response->assertSee($this->donation->public_id);
    $response->assertDontSee($failedDonation->public_id);
});

it('filters donations by source counting element and null as checkout modal', function () {
    $campaignPageDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'source' => 'campaign_page',
    ]);

    $elementDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'source' => 'element',
    ]);

    // $this->donation has a null source; element and null both display under Checkout Modal.
    Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->set('sourceFilter', ['checkout_modal'])
        ->assertSee($this->donation->public_id)
        ->assertSee($elementDonation->public_id)
        ->assertDontSee($campaignPageDonation->public_id)
        ->set('sourceFilter', ['campaign_page'])
        ->assertSee($campaignPageDonation->public_id)
        ->assertDontSee($this->donation->public_id)
        ->assertDontSee($elementDonation->public_id)
        ->set('sourceFilter', [])
        ->assertSee($this->donation->public_id)
        ->assertSee($campaignPageDonation->public_id);
});

it('resets all filters at once', function () {
    Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->assertDontSee('Reset filters')
        ->set('statusFilter', 'succeeded')
        ->set('sourceFilter', ['campaign_page'])
        ->set('period', '7_days')
        ->set('search', 'zara')
        ->assertSee('Reset filters')
        ->call('resetFilters')
        ->assertSet('statusFilter', '')
        ->assertSet('sourceFilter', [])
        ->assertSet('period', 'all_time')
        ->assertSet('search', '')
        ->assertDontSee('Reset filters');
});

it('does not offer element as a source option', function () {
    $component = Livewire::actingAs($this->user)->test(DonationIndex::class);

    expect($component->instance()->sourceOptions())
        ->toBe([
            'checkout_modal' => 'Checkout Modal',
            'campaign_page' => 'Campaign Page',
            'virtual_terminal' => 'Virtual Terminal',
        ]);
});

it('labels element donations as checkout modal', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'source' => 'element',
    ]);

    expect($donation->source_label)->toBe('Checkout Modal');
});

it('filters donations by element token and payment method type', function () {
    $element = Element::factory()->create([
        'organization_id' => $this->organization->id,
        'campaign_id' => $this->campaign->id,
    ]);

    $elementDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'utm_params' => ['source' => 'element', 'element_token' => $element->token],
    ]);

    $fpxDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'payment_method_type' => 'fpx',
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->set('elementFilter', [$element->token])
        ->assertSee($elementDonation->public_id)
        ->assertDontSee($this->donation->public_id)
        ->assertDontSee($fpxDonation->public_id)
        ->call('clearElementFilter')
        ->set('paymentMethodFilter', ['fpx'])
        ->assertSee($fpxDonation->public_id)
        ->assertDontSee($elementDonation->public_id)
        ->call('clearPaymentMethodFilter')
        ->assertSee($elementDonation->public_id)
        ->assertSee($fpxDonation->public_id);
});

it('shows a combined source chip label for multiple selections', function () {
    $component = Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->set('sourceFilter', ['checkout_modal', 'campaign_page', 'virtual_terminal']);

    expect($component->instance()->sourceChipLabel())->toBe('Checkout Modal and 2 more');

    $component->set('sourceFilter', ['virtual_terminal']);

    expect($component->instance()->sourceChipLabel())->toBe('Virtual Terminal');

    $component->call('clearSourceFilter');

    expect($component->instance()->sourceChipLabel())->toBe('Source');
});

it('searches donations by donor name', function () {
    $otherDonor = Donor::factory()->create(['name' => 'Zara Unique']);
    $otherDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $otherDonor->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('app.donations.index', ['search' => $otherDonor->name]));

    $response->assertStatus(200);
    $response->assertSee($otherDonation->public_id);
    $response->assertDontSee($this->donation->public_id);
});

it('displays donation detail sections', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Donation amount')
        ->assertSee('Payment & fees')
        ->assertSee('Personal information')
        ->assertSee('Source')
        ->assertSee('Insights')
        ->assertSee('Receipts')
        ->assertSee($this->donor->name)
        ->assertSee($this->campaign->title);
});

it('formats MYR amounts as MYR {amount} without duplicating the symbol', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('MYR 150.00')
        ->assertSee('MYR 145.50')
        ->assertDontSee('MYR 150.00 MYR')
        ->assertDontSee('MYR 145.50 MYR');
});

it('does not double convert processing fee base for foreign currency donations', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => 407.84,
        'base_currency' => 'myr',
        'exchange_rate' => 4.078410,
        'processing_fee' => 10.93,
        'stripe_fee' => 27.23,
        'net_amount' => 399.04,
        'status' => 'succeeded',
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $donation])
        ->assertSee('USD 6.68')
        ->assertSee('MYR 27.23')
        ->assertSee('MYR 10.93')
        ->assertDontSee('MYR 111.05')
        ->assertDontSee('MYR 44.58');
});

it('shows the full charged amount and fee cover for fee-covered donations', function () {
    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'donor_fee_covered' => 7.20,
        'base_amount' => 407.84,
        'base_currency' => 'myr',
        'exchange_rate' => 4.078410,
        'processing_fee' => 10.93,
        'stripe_fee' => 27.23,
        'net_amount' => 399.04,
        'status' => 'succeeded',
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $donation])
        // Payment amount = gross + fee cover (what the donor was actually charged)
        ->assertSee('USD 107.20')
        ->assertSee('MYR 437.20')
        // Before fees covered = the donation amount itself
        ->assertSee('Before fees covered')
        ->assertSee('USD 100.00')
        ->assertSee('Covered · USD 7.20')
        // gross - fee cover double-subtraction must not appear
        ->assertDontSee('USD 92.80');
});

it('hides the before fees covered row when no fee was covered', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Payment amount')
        ->assertDontSee('Before fees covered')
        ->assertSee('Not covered');
});

it('shows the payment intent id as the payment id when available', function () {
    $this->donation->update([
        'stripe_payment_intent_id' => 'pi_test_123',
        'stripe_charge_id' => 'ch_test_123',
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('pi_test_123')
        ->assertDontSee('ch_test_123');
});

it('falls back to the charge id as the payment id when no intent exists', function () {
    $this->donation->update([
        'stripe_payment_intent_id' => null,
        'stripe_charge_id' => 'ch_test_123',
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('ch_test_123');
});

it('shows refund modal and validates reason', function () {
    $this->donation->update(['stripe_charge_id' => 'ch_test_123']);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->call('openRefundModal')
        ->assertSet('showRefundModal', true)
        ->call('confirmRefund')
        ->assertHasErrors(['refundReason' => 'required'])
        ->set('refundReason', 'duplicate')
        ->assertSet('refundReason', 'duplicate');
});

it('shows approximate myr total for foreign donations without base amount', function () {
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'currency' => 'usd',
        'gross_amount' => 150.00,
        'base_amount' => null,
        'status' => 'succeeded',
    ]);

    $response = $this->actingAs($this->user)->get(route('app.donations.index'));

    $response->assertOk()
        ->assertSee('≈ MYR')
        ->assertSee('USD 150.00');
});

it('renders date and donation columns without wrapping', function () {
    $response = $this->actingAs($this->user)->get(route('app.donations.index'));

    $response->assertOk()
        ->assertSeeHtml('class="whitespace-nowrap min-w-[180px] px-5 py-4 text-sm text-slate-500"')
        ->assertSeeHtml('class="whitespace-nowrap min-w-[180px] px-5 py-4">');
});

it('redirects guests to login', function () {
    $this->get(route('app.donations.index'))->assertRedirect(route('login'));
    $this->get(route('app.donations.show', $this->donation))->assertRedirect(route('login'));
});

it('links the source element to the element edit page', function () {
    $element = Element::factory()->create([
        'organization_id' => $this->organization->id,
        'campaign_id' => $this->campaign->id,
    ]);

    $this->donation->update([
        'utm_params' => [
            'source' => 'element',
            'element_token' => $element->token,
            'element_type' => 'button',
            'element_name' => $element->name,
        ],
    ]);
    $this->donation->refresh();

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee($element->name)
        ->assertSeeHtml('href="'.e(route('app.elements.edit', $element)).'"');
});

it('renders the emails section with sent emails for the donation', function () {
    Mail::fake();

    $log = DonorEmailLog::factory()->donation($this->donation)->create([
        'subject' => 'Your Donation Receipt — '.$this->organization->name,
        'sent_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertStatus(200)
        ->assertSee('Emails')
        ->assertSee('Sent')
        ->assertSee('Subject')
        ->assertSee('Opened')
        ->assertSee('Resend')
        ->assertSee('Your Donation Receipt — '.$this->organization->name);
});

it('shows empty state when no emails have been sent for the donation', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Emails')
        ->assertSee('No emails yet');
});

it('does not show email logs from other donations', function () {
    Mail::fake();

    $otherDonation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    DonorEmailLog::factory()->donation($otherDonation)->create([
        'subject' => 'Other Donation Email',
        'sent_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Emails')
        ->assertDontSee('Other Donation Email')
        ->assertSee('No emails yet');
});

it('does not show donation email logs from other organizations', function () {
    Mail::fake();

    $otherOrganization = Organization::factory()->create();
    $otherCampaign = Campaign::factory()->for($otherOrganization)->create();
    $otherDonation = Donation::factory()->for($this->donor)->for($otherCampaign)->create();

    DonorEmailLog::factory()->donation($otherDonation)->create([
        'organization_id' => $otherOrganization->id,
        'subject' => 'Other Org Email',
        'sent_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Emails')
        ->assertDontSee('Other Org Email')
        ->assertSee('No emails yet');
});

it('resends a donation receipt email and creates a new log entry', function () {
    Mail::fake();

    $log = DonorEmailLog::factory()->donation($this->donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$this->organization->name,
        'sent_at' => now()->subDay(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation]);

    $component->call('confirmResend', $log->getKey())
        ->assertSet('showResendModal', true)
        ->assertSet('resendLogId', $log->getKey())
        ->assertSet('resendRecipientEmail', $this->donor->email);

    $component->call('resendConfirmed')
        ->assertSet('showResendModal', false)
        ->assertDispatched('notify', variant: 'success');

    Mail::assertQueued(DonationReceipt::class, function (DonationReceipt $mail) {
        return $mail->donation->is($this->donation);
    });

    expect($log->fresh()->resends)->toHaveCount(1);
});

it('previews a donation receipt email with rendered html', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->call('previewEmail', DonorEmailLog::factory()->donation($this->donation)->create([
            'mailable_class' => DonationReceipt::class,
            'subject' => 'Your Donation Receipt — '.$this->organization->name,
        ])->getKey())
        ->assertSet('showPreviewModal', true)
        ->assertSet('previewSubject', 'Your Donation Receipt — '.$this->organization->name)
        ->assertSet('previewHtml', fn ($html) => is_string($html) && str_contains($html, 'Thank you for your donation!'));
});

it('shows a resent badge next to the subject for resend log entries', function () {
    $originalLog = DonorEmailLog::factory()->donation($this->donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$this->organization->name,
    ]);

    $resentLog = DonorEmailLog::factory()->donation($this->donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$this->organization->name,
        'resent_from_id' => $originalLog->getKey(),
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee($resentLog->subject)
        ->assertSee('Resent');
});

it('displays refunded state on the donation show page', function () {
    $this->donation->update([
        'status' => 'refunded',
        'refunded_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Refunded')
        ->assertSee('Refund date')
        ->assertSee(myrTime($this->donation->refunded_at))
        ->assertDontSeeHTML('wire:click="openRefundModal"')
        ->assertDontSeeHTML('href="'.e(route('donations.receipt.download', $this->donation)).'"')
        ->assertSee('Receipts')
        ->assertSee('Amounts below reflect the original transaction before the refund.');
});

it('warns when a refunded donation belongs to an active recurring plan', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => 'active',
    ]);

    $this->donation->update([
        'subscription_id' => $subscription->id,
        'status' => 'refunded',
        'refunded_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Recurring plan')
        ->assertSee('This installment was refunded, but the recurring plan is still active.');
});

it('detects stripe as the default payment processor', function () {
    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Payment processor')
        ->assertSee('Stripe');
});

it('detects chip as the payment processor from chip identifiers', function () {
    $this->donation->update(['chip_purchase_id' => 'chip_test_123']);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Payment processor')
        ->assertSee('CHIP')
        ->assertSeeHtml('src="'.e(asset('images/payment-processors/chip.svg')).'"');
});

it('displays credit card expiry date when available', function () {
    $this->donation->update([
        'payment_method_brand' => 'visa',
        'payment_method_last4' => '4242',
        'payment_method_exp_month' => 12,
        'payment_method_exp_year' => 2025,
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Visa')
        ->assertSee('•••• 4242')
        ->assertSee('(Exp. 12/25)');
});

it('does not display expiry date when card expiry is missing', function () {
    $this->donation->update([
        'payment_method_brand' => 'mastercard',
        'payment_method_last4' => '5555',
    ]);

    Livewire::actingAs($this->user)
        ->test(DonationShow::class, ['donation' => $this->donation])
        ->assertSee('Mastercard')
        ->assertSee('•••• 5555')
        ->assertDontSee('(Exp.');
});

it('filters donations by frequency and custom date range from query string', function () {
    $oneTimeToday = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'type' => 'one_time',
        'status' => 'succeeded',
        'created_at' => now(),
    ]);

    $recurringToday = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'type' => 'recurring',
        'status' => 'succeeded',
        'created_at' => now(),
    ]);

    $oneTimeOld = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'type' => 'one_time',
        'status' => 'succeeded',
        'created_at' => now()->subDays(5),
    ]);

    $component = Livewire::actingAs($this->user)
        ->withQueryParams([
            'frequencyFilter' => 'one_time',
            'period' => 'custom',
            'dateFrom' => now()->format('Y-m-d'),
            'dateTo' => now()->format('Y-m-d'),
        ])
        ->test(DonationIndex::class);

    $ids = $component->instance()->donations()->pluck('id');

    expect($ids)->toContain($oneTimeToday->id);
    expect($ids)->not->toContain($recurringToday->id);
    expect($ids)->not->toContain($oneTimeOld->id);
});

it('displays average donation amount', function () {
    // beforeEach donation has base_amount 100.00; add one more for 250.50.
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'base_amount' => 250.50,
        'status' => 'succeeded',
    ]);

    // (100 + 250.50) / 2 = 175.25
    Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->assertStatus(200)
        ->assertSee('Avg Donation')
        ->assertSee('MYR 175.25');
});

it('displays succeeded count with failed and refunded breakdown', function () {
    Donation::factory()->count(2)->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => 'failed',
    ]);

    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => 'refunded',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->assertStatus(200)
        ->assertSee('Succeeded')
        ->assertSee('2 failed · 1 refunded');

    expect($component->instance()->succeededCount)->toBe(1);
});

it('shows new donations this month trend on the total donations card', function () {
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'created_at' => now()->subMonths(2),
    ]);

    // Only the beforeEach donation was created this month.
    Livewire::actingAs($this->user)
        ->test(DonationIndex::class)
        ->assertStatus(200)
        ->assertSee('+1 this month');
});

it('syncs donor personal information changes to stripe', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'stripe_customer_id' => 'cus_test_456',
        'first_name' => 'Ali',
        'last_name' => 'Abu',
        'email' => 'ali@example.com',
    ]);
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $spy = Mockery::mock(SyncDonorDetailsToStripe::class);
    $spy->shouldReceive('sync')
        ->once()
        ->withArgs(fn (Donor $d, Organization $o) => $d->is($donor) && $o->is($organization))
        ->andReturn(true);
    app()->instance(SyncDonorDetailsToStripe::class, $spy);

    Livewire::actingAs($user)
        ->test(DonationShow::class, ['donation' => $donation])
        ->call('openEditPersonalModal')
        ->set('editFirstName', 'Siti')
        ->set('editLastName', 'Aminah')
        ->set('editEmail', 'siti@example.com')
        ->call('savePersonalInformation')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    expect($donor->fresh())
        ->name->toBe('Siti Aminah')
        ->email->toBe('siti@example.com');
});

it('does not sync donor personal information to stripe when donor has no customer id', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->for($organization)->create(['role' => UserRole::NgoAdmin]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'stripe_customer_id' => null,
        'first_name' => 'Ali',
        'last_name' => 'Abu',
        'email' => 'ali@example.com',
    ]);
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $spy = Mockery::mock(SyncDonorDetailsToStripe::class);
    $spy->shouldReceive('sync')
        ->never();
    app()->instance(SyncDonorDetailsToStripe::class, $spy);

    Livewire::actingAs($user)
        ->test(DonationShow::class, ['donation' => $donation])
        ->call('openEditPersonalModal')
        ->set('editFirstName', 'Siti')
        ->set('editLastName', 'Aminah')
        ->set('editEmail', 'siti@example.com')
        ->call('savePersonalInformation')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    expect($donor->fresh())
        ->name->toBe('Siti Aminah')
        ->email->toBe('siti@example.com');
});
