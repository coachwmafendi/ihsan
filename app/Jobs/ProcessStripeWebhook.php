<?php

namespace App\Jobs;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Stripe\BalanceTransaction;
use Stripe\Customer as StripeCustomer;
use Stripe\Event as StripeEvent;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Price as StripePrice;
use Stripe\Product as StripeProduct;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class ProcessStripeWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $payload,
    ) {}

    public function handle(): void
    {
        $event = StripeEvent::constructFrom(json_decode($this->payload, true));

        $log = WebhookLog::query()->create([
            'stripe_event_id' => $event->id,
            'event_type' => $event->type,
            'payload' => $event->toArray(),
            'status' => 'processing',
        ]);

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event),
            'invoice.paid' => $this->handleInvoicePaid($event),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'account.updated' => $this->handleAccountUpdated($event),
            default => null,
        };

        $log->update(['status' => 'completed']);
    }

    private function handlePaymentIntentSucceeded(StripeEvent $event): void
    {
        $paymentIntent = $event->data->object;
        $donationId = $paymentIntent->metadata->donation_id ?? null;

        if ($donationId === null) {
            return;
        }

        $donation = Donation::query()->find($donationId);
        if ($donation === null) {
            return;
        }

        $cardBrand = null;
        $cardLast4 = null;

        if ($paymentIntent->payment_method) {
            try {
                $paymentMethod = PaymentMethod::retrieve($paymentIntent->payment_method);

                if ($paymentMethod->type === 'card' && $paymentMethod->card !== null) {
                    $cardBrand = $paymentMethod->card->brand;
                    $cardLast4 = $paymentMethod->card->last4;
                }
            } catch (\Exception $e) {
                // Card details are non-critical
            }
        }

        $chargeId = $paymentIntent->charges->data[0]->id ?? null;
        $stripeFee = 0;
        $balanceTransaction = $paymentIntent->charges->data[0]->balance_transaction ?? null;

        if ($balanceTransaction) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $bt = BalanceTransaction::retrieve($balanceTransaction);
                $stripeFee = (float) ($bt->fee / 100);
            } catch (\Exception $e) {
                // If we can't retrieve the balance transaction, leave fee at 0
            }
        }

        $donation->update([
            'status' => DonationStatus::Succeeded,
            'stripe_charge_id' => $chargeId,
            'stripe_fee' => $stripeFee,
            'payment_method_brand' => $cardBrand,
            'payment_method_last4' => $cardLast4,
            'net_amount' => (float) $donation->gross_amount - $stripeFee - (float) $donation->platform_fee,
        ]);

        $donation->campaign()->increment('collected_amount', (float) $donation->gross_amount);

        if ($donation->type === DonationType::Recurring) {
            $this->createRecurringSubscription($donation, $paymentIntent);
        }

        SendDonationReceipt::dispatch($donation);
    }

    private function handlePaymentIntentFailed(StripeEvent $event): void
    {
        $paymentIntent = $event->data->object;
        $donationId = $paymentIntent->metadata->donation_id ?? null;

        if ($donationId === null) {
            return;
        }

        Donation::query()->whereKey($donationId)->update([
            'status' => DonationStatus::Failed,
        ]);
    }

    private function handleInvoicePaid(StripeEvent $event): void
    {
        $invoice = $event->data->object;
        $subscriptionId = $invoice->subscription;

        if ($subscriptionId === null) {
            return;
        }

        $subscription = Subscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if ($subscription === null) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'retry_count' => 0,
            'current_period_start' => now()->setTimestamp($invoice->period_start ?? $invoice->created),
            'current_period_end' => now()->setTimestamp($invoice->period_end),
        ]);

        $donation = Donation::query()->create([
            'campaign_id' => $subscription->campaign_id,
            'donor_id' => $subscription->donor_id,
            'subscription_id' => $subscription->getKey(),
            'gross_amount' => (float) ($invoice->amount_paid / 100),
            'stripe_fee' => 0,
            'platform_fee' => 0,
            'net_amount' => (float) ($invoice->amount_paid / 100),
            'currency' => $invoice->currency,
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::Recurring,
            'stripe_payment_intent_id' => $invoice->payment_intent,
            'stripe_charge_id' => $invoice->charge,
        ]);

        $donation->campaign()->increment('collected_amount', (float) $donation->gross_amount);

        SendDonationReceipt::dispatch($donation);
    }

    private function handleInvoicePaymentFailed(StripeEvent $event): void
    {
        $invoice = $event->data->object;
        $subscriptionId = $invoice->subscription;

        if ($subscriptionId === null) {
            return;
        }

        $subscription = Subscription::query()
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if ($subscription === null) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::PastDue,
            'retry_count' => $subscription->retry_count + 1,
        ]);
    }

    private function handleSubscriptionDeleted(StripeEvent $event): void
    {
        $stripeSubscription = $event->data->object;

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscription->id)
            ->update(['status' => SubscriptionStatus::Cancelled, 'cancelled_at' => now()]);
    }

    private function handleSubscriptionUpdated(StripeEvent $event): void
    {
        $stripeSubscription = $event->data->object;

        $status = match ($stripeSubscription->status) {
            'active' => SubscriptionStatus::Active,
            'past_due' => SubscriptionStatus::PastDue,
            'canceled' => SubscriptionStatus::Cancelled,
            'incomplete' => SubscriptionStatus::Incomplete,
            'paused' => SubscriptionStatus::Paused,
            default => null,
        };

        if ($status === null) {
            return;
        }

        Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscription->id)
            ->update(['status' => $status]);
    }

    private function createRecurringSubscription(Donation $donation, StripePaymentIntent $paymentIntent): void
    {
        $paymentMethodId = $paymentIntent->payment_method;
        if ($paymentMethodId === null) {
            return;
        }

        $donorEmail = $paymentIntent->metadata->donor_email ?? $donation->donor?->email;
        if ($donorEmail === null) {
            return;
        }

        $customer = StripeCustomer::all(['email' => $donorEmail, 'limit' => 1])->first()
            ?? StripeCustomer::create([
                'email' => $donorEmail,
                'name' => $donation->donor?->name,
                'metadata' => [
                    'donor_id' => (string) $donation->donor_id,
                ],
            ]);

        StripePaymentIntent::update($paymentIntent->id, [
            'customer' => $customer->id,
        ]);

        $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
        $paymentMethod->attach(['customer' => $customer->id]);
        $customer->invoice_settings = ['default_payment_method' => $paymentMethodId];
        $customer->save();

        $product = StripeProduct::create([
            'name' => $donation->campaign?->title ?? 'Donation',
            'metadata' => ['campaign_id' => (string) $donation->campaign_id],
        ]);

        $price = StripePrice::create([
            'product' => $product->id,
            'currency' => $donation->currency,
            'unit_amount' => (int) ((float) $donation->gross_amount * 100),
            'recurring' => ['interval' => 'month'],
        ]);

        $stripeSubscription = StripeSubscription::create([
            'customer' => $customer->id,
            'items' => [['price' => $price->id]],
            'metadata' => [
                'campaign_id' => (string) $donation->campaign_id,
                'donor_id' => (string) $donation->donor_id,
            ],
        ]);

        $subscription = Subscription::query()->create([
            'campaign_id' => $donation->campaign_id,
            'donor_id' => $donation->donor_id,
            'stripe_subscription_id' => $stripeSubscription->id,
            'stripe_price_id' => $price->id,
            'amount' => (float) $donation->gross_amount,
            'currency' => $donation->currency,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
            'current_period_start' => now()->setTimestamp($stripeSubscription->current_period_start),
            'current_period_end' => now()->setTimestamp($stripeSubscription->current_period_end),
        ]);

        $donation->update(['subscription_id' => $subscription->getKey()]);
    }

    private function handleAccountUpdated(StripeEvent $event): void
    {
        $account = $event->data->object;

        Organization::query()
            ->where('stripe_account_id', $account->id)
            ->update([
                'stripe_onboarded' => $account->charges_enabled,
            ]);
    }
}
