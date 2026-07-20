<?php

use App\Actions\Stripe\ChargeRecurringInstallment;
use App\Data\ChargeResult;
use App\Enums\DonationStatus;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Livewire\App\Subscriptions\SubscriptionShow;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\DonorPaymentMethod;
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
});

it('renders the subscription show page', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertStatus(200)
        ->assertSee('Recurring plan')
        ->assertSee($subscription->public_id);
});

it('shows the active recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'payment_count' => 3,
        'current_period_end' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This monthly recurring donation is active.')
        ->assertSee('Installment #4')
        ->assertSee(myrTime($subscription->current_period_end));
});

it('projects the next installment date when current period end is stale', function () {
    $staleDate = now()->subDay()->startOfDay();
    $expectedDate = $staleDate->copy()->addMonth();

    while ($expectedDate->isPast()) {
        $expectedDate->addMonth();
    }

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'payment_count' => 3,
        'current_period_end' => $staleDate,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This monthly recurring donation is active.')
        ->assertSee('Installment #4')
        ->assertSee(myrTime($expectedDate));
});

it('projects the next installment date for non-monthly intervals', function () {
    $staleDate = now()->subDay()->startOfDay();
    $expectedDate = $staleDate->copy()->addMonths(3);

    while ($expectedDate->isPast()) {
        $expectedDate->addMonths(3);
    }

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Quarterly,
        'payment_count' => 2,
        'current_period_end' => $staleDate,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This quarterly recurring donation is active.')
        ->assertSee('Installment #3')
        ->assertSee(myrTime($expectedDate));
});

it('shows the scheduled cancellation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'cancel_at_period_end' => true,
        'current_period_end' => now()->addWeek(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('Scheduled to cancel.')
        ->assertSee('final installment will be charged on')
        ->assertSee(myrTime($subscription->current_period_end));
});

it('shows the paused recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Paused,
        'interval' => SubscriptionInterval::Monthly,
        'paused_until' => now()->addWeeks(2),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This monthly recurring donation is paused.')
        ->assertSee('Installments will resume on')
        ->assertSee(myrTime($subscription->paused_until));
});

it('shows the cancelled recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Cancelled,
        'interval' => SubscriptionInterval::Monthly,
        'cancelled_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This recurring donation was cancelled on')
        ->assertSee(myrTime($subscription->cancelled_at))
        ->assertSee('No further charges will be made.');
});

it('shows the past due recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::PastDue,
        'interval' => SubscriptionInterval::Monthly,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('The most recent installment failed.')
        ->assertSee('We will retry the payment shortly.');
});

it('shows the incomplete recurring donation status banner', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Incomplete,
        'interval' => SubscriptionInterval::Monthly,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('This recurring donation is incomplete.')
        ->assertSee('Please complete the payment setup.');
});

it('opens the edit payment details modal', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'amount' => 35.00,
        'currency' => 'sgd',
        'cover_fee' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openEditPaymentDetailsModal')
        ->assertSet('showEditPaymentDetailsModal', true)
        ->assertSet('editAmount', 35.00)
        ->assertSet('editInterval', 'monthly')
        ->assertSet('editCoverFee', true)
        ->assertSee('Edit payment details')
        ->assertSee('Installment amount')
        ->assertSee('Frequency');
});

it('validates payment details form', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openEditPaymentDetailsModal')
        ->set('editAmount', 0)
        ->call('savePaymentDetails')
        ->assertHasErrors(['editAmount']);
});

it('opens the skip installments modal and computes next installment date', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'current_period_end' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openSkipModal')
        ->assertSet('showSkipModal', true)
        ->assertSet('skipDuration', '1')
        ->assertSee('Skip installments')
        ->set('skipDuration', 'custom')
        ->set('customSkipMonths', 3)
        ->assertSee(myrTime($subscription->current_period_end->copy()->addMonths(3)));
});

it('shows approximate myr total when subscription donations lack base amount', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'currency' => 'usd',
        'amount' => 50.00,
    ]);

    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $subscription->id,
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'base_amount' => null,
    ]);
    Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $subscription->id,
        'currency' => 'usd',
        'gross_amount' => 50.00,
        'base_amount' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('≈ MYR 100.00');
});

it('shows converted myr amount, payment method and installment number in the installments table', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'currency' => 'sgd',
        'amount' => 50.00,
    ]);

    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $subscription->id,
        'currency' => 'sgd',
        'gross_amount' => 50.00,
        'base_amount' => null,
        'exchange_rate' => 3.16,
        'payment_method_brand' => 'visa',
        'payment_method_last4' => '4242',
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('≈ MYR 158.00')
        ->assertSee($donation->public_id)
        ->assertSee($donation->formatted_amount)
        ->assertSee('Visa **** 4242')
        ->assertSee('1st installment')
        ->assertSee('1');
});

it('shows converted myr amount with original currency tooltip in the receipts table', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'currency' => 'sgd',
    ]);

    $donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'subscription_id' => $subscription->id,
        'currency' => 'sgd',
        'gross_amount' => 50.00,
        'base_amount' => null,
        'exchange_rate' => 3.16,
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('≈ MYR 158.00')
        ->assertSee($donation->formatted_amount);
});

it('renders the emails section with sent emails for the subscription', function () {
    Mail::fake();

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    DonorEmailLog::factory()->subscription($subscription)->create([
        'subject' => 'Thank you for joining as a recurring supporter',
        'sent_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertStatus(200)
        ->assertSee('Emails')
        ->assertSee('Sent')
        ->assertSee('Subject')
        ->assertSee('Opened')
        ->assertSee('Resend')
        ->assertSee('Thank you for joining as a recurring supporter');
});

it('shows empty state when no emails have been sent for the subscription', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('Emails')
        ->assertSee('No emails yet');
});

it('does not show subscription email logs from other subscriptions', function () {
    Mail::fake();

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    $otherSubscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    DonorEmailLog::factory()->subscription($otherSubscription)->create([
        'subject' => 'Other Subscription Email',
        'sent_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('Emails')
        ->assertDontSee('Other Subscription Email')
        ->assertSee('No emails yet');
});

it('resends a subscription email and creates a new log entry', function () {
    Mail::fake();

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    $donation = Donation::factory()->for($this->donor)->for($this->campaign)->create([
        'subscription_id' => $subscription->id,
    ]);

    $log = DonorEmailLog::factory()->subscription($subscription)->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Thank you for joining as a recurring supporter',
        'sent_at' => now()->subDay(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription]);

    $component->call('confirmResend', $log->getKey())
        ->assertSet('showResendModal', true)
        ->assertSet('resendLogId', $log->getKey())
        ->assertSet('resendRecipientEmail', $this->donor->email);

    $component->call('resendConfirmed')
        ->assertSet('showResendModal', false)
        ->assertDispatched('notify', variant: 'success');

    Mail::assertQueued(DonationReceipt::class, function (DonationReceipt $mail) use ($donation) {
        return $mail->donation->is($donation);
    });

    expect($log->fresh()->resends)->toHaveCount(1);
});

it('previews a subscription email with rendered html', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);

    $donation = Donation::factory()->for($this->donor)->for($this->campaign)->create([
        'subscription_id' => $subscription->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('previewEmail', DonorEmailLog::factory()->subscription($subscription)->donation($donation)->create([
            'mailable_class' => DonationReceipt::class,
            'subject' => 'Thank you for joining as a recurring supporter',
        ])->getKey())
        ->assertSet('showPreviewModal', true)
        ->assertSet('previewSubject', 'Thank you for joining as a recurring supporter')
        ->assertSet('previewHtml', fn ($html) => is_string($html) && str_contains($html, 'Thank you for your donation!'));
});

it('shows scheduled to cancel ui adjustments', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'cancel_at_period_end' => true,
        'current_period_end' => now()->addWeek(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('Scheduled to cancel')
        ->assertSee('Final installment date')
        ->assertSee('Cancellation scheduled')
        ->assertSee('Final installment on '.myrTime($subscription->current_period_end))
        ->assertDontSee('Offer an increase');
});

it('blocks manage actions when subscription is scheduled to cancel', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'cancel_at_period_end' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openEditPaymentDetailsModal')
        ->assertDispatched('notify', type: 'error')
        ->call('openSkipModal')
        ->assertDispatched('notify', type: 'error')
        ->call('openUpgradeModal')
        ->assertDispatched('notify', type: 'error')
        ->call('openCancelModal')
        ->assertDispatched('notify', type: 'error')
        ->call('pauseSubscription')
        ->assertDispatched('notify', type: 'error');
});

it('cancels an app-controlled subscription immediately from the admin panel', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'stripe_subscription_id' => null,
        'chip_recurring_token' => null,
        'next_charge_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('Cancel recurring')
        ->call('openCancelModal')
        ->call('cancelSubscription')
        ->assertDispatched('notify', type: 'success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->cancelled_at)->not->toBeNull()
        ->and($subscription->next_charge_at)->toBeNull();
});

it('updates payment details for an app-controlled subscription', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'stripe_subscription_id' => null,
        'chip_recurring_token' => null,
        'amount' => 50.00,
        'currency' => 'myr',
        'cover_fee' => false,
        'next_charge_at' => now()->addMonth()->setDay(15)->startOfDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openEditPaymentDetailsModal')
        ->set('editAmount', 75.00)
        ->set('editInterval', 'monthly')
        ->set('editBillingDay', 20)
        ->set('editCoverFee', true)
        ->call('savePaymentDetails')
        ->assertDispatched('notify', type: 'success');

    $subscription->refresh();

    expect($subscription->amount)->toEqual(75.00)
        ->and($subscription->cover_fee)->toBeTrue()
        ->and((int) $subscription->next_charge_at->format('j'))->toBe(20);
});

it('processes an immediate payment for an app-controlled subscription from edit details', function () {
    $paymentMethod = DonorPaymentMethod::create([
        'donor_id' => $this->donor->getKey(),
        'stripe_payment_method_id' => 'pm_test_'.uniqid(),
        'brand' => 'Visa',
        'last4' => '4242',
        'is_default' => true,
    ]);

    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'donor_payment_method_id' => $paymentMethod->getKey(),
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'stripe_subscription_id' => null,
        'chip_recurring_token' => null,
        'amount' => 50.00,
        'currency' => 'myr',
        'next_charge_at' => now()->addMonth(),
    ]);

    $action = Mockery::mock(ChargeRecurringInstallment::class);
    $action->shouldReceive('handle')
        ->once()
        ->with(Mockery::on(fn ($sub) => $sub->is($subscription)))
        ->andReturn(new ChargeResult('succeeded'));
    $this->instance(ChargeRecurringInstallment::class, $action);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openEditPaymentDetailsModal')
        ->set('editProcessPaymentNow', true)
        ->call('savePaymentDetails')
        ->assertDispatched('notify', type: 'success');
});

it('shows ended state and blocks manage actions for cancelled subscriptions', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Cancelled,
        'interval' => SubscriptionInterval::Monthly,
        'cancelled_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->assertSee('Ended')
        ->assertSee('This recurring plan has ended')
        ->assertSee('This recurring donation was cancelled on')
        ->call('openEditPaymentDetailsModal')
        ->assertDispatched('notify', type: 'error');
});

it('pauses an app-controlled subscription from the admin panel', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'stripe_subscription_id' => null,
        'chip_recurring_token' => null,
        'next_charge_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('pauseSubscription')
        ->assertDispatched('notify', type: 'success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Paused)
        ->and($subscription->paused_until)->not->toBeNull()
        ->and($subscription->next_charge_at)->not->toBeNull();
});

it('skips installments for an app-controlled subscription from the admin panel', function () {
    $subscription = Subscription::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
        'status' => SubscriptionStatus::Active,
        'interval' => SubscriptionInterval::Monthly,
        'stripe_subscription_id' => null,
        'chip_recurring_token' => null,
        'next_charge_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->user)
        ->test(SubscriptionShow::class, ['subscription' => $subscription])
        ->call('openSkipModal')
        ->set('skipDuration', '3')
        ->call('confirmSkip')
        ->assertDispatched('notify', type: 'success');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::Paused)
        ->and($subscription->paused_until)->not->toBeNull()
        ->and($subscription->next_charge_at)->not->toBeNull();
});
