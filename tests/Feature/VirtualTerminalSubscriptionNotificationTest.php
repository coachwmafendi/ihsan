<?php

declare(strict_types=1);

use App\Actions\Stripe\ProcessVirtualTerminalSubscription;
use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\DonationType;
use App\Jobs\SendDonorNewSubscriptionNotification;
use App\Jobs\SendNewSubscriptionNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function invokeRecordFirstInstallment(array $latestInvoice, Subscription $subscription, string $currency = 'myr'): void
{
    $stripeSubscription = Stripe\Subscription::constructFrom(['id' => 'sub_vt', 'latest_invoice' => $latestInvoice]);

    $action = app(ProcessVirtualTerminalSubscription::class);
    $method = new ReflectionMethod($action, 'recordFirstInstallment');
    $method->invoke($action, $stripeSubscription, $subscription, 100.0, 0.0, $currency, 'virtual_terminal', []);
}

beforeEach(function () {
    $syncSpy = Mockery::mock(SyncDonationStripeDetails::class);
    $syncSpy->shouldReceive('sync')->andReturn(['payment_intent' => null, 'charge_id' => null]);
    app()->instance(SyncDonationStripeDetails::class, $syncSpy);
});

it('records the first installment donation and notifies on a new VT subscription', function () {
    Queue::fake();

    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $subscription = Subscription::factory()->for($campaign)->create();

    invokeRecordFirstInstallment([
        'id' => 'in_vt_test',
        'payment_intent' => ['id' => 'pi_vt_sub'],
    ], $subscription);

    $this->assertDatabaseHas('donations', [
        'subscription_id' => $subscription->id,
        'stripe_payment_intent_id' => 'pi_vt_sub',
        'type' => DonationType::Recurring->value,
        'source' => 'virtual_terminal',
    ]);

    Queue::assertPushed(SendNewSubscriptionNotification::class);
    Queue::assertPushed(SendDonorNewSubscriptionNotification::class);
});

it('does not duplicate the donation when the payment intent already exists', function () {
    Queue::fake();

    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $subscription = Subscription::factory()->for($campaign)->create();

    $invoice = ['id' => 'in_vt_test', 'payment_intent' => ['id' => 'pi_vt_dupe']];

    invokeRecordFirstInstallment($invoice, $subscription);
    invokeRecordFirstInstallment($invoice, $subscription);

    expect(Donation::where('stripe_payment_intent_id', 'pi_vt_dupe')->count())->toBe(1);
});

it('skips when the invoice has no payment intent', function () {
    Queue::fake();

    $organization = Organization::factory()->stripeConnected()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $subscription = Subscription::factory()->for($campaign)->create();

    invokeRecordFirstInstallment(['id' => 'in_x', 'payment_intent' => null], $subscription);

    expect(Donation::count())->toBe(0);
    Queue::assertNotPushed(SendNewSubscriptionNotification::class);
});
