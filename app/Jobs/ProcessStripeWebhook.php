<?php

namespace App\Jobs;

use App\Actions\Stripe\CreateRecurringSubscription;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\Event as StripeEvent;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class ProcessStripeWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $payload,
    ) {}

    public function handle(): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $event = StripeEvent::constructFrom(json_decode($this->payload, true));

        $log = WebhookLog::query()->firstOrCreate([
            'stripe_event_id' => $event->id,
        ], [
            'event_type' => $event->type,
            'payload' => $event->toArray(),
            'status' => 'processing',
        ]);

        if (! $log->wasRecentlyCreated && $log->status === 'completed') {
            return;
        }

        $log->update([
            'event_type' => $event->type,
            'payload' => $event->toArray(),
            'status' => 'processing',
            'error_message' => null,
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

        $log->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    private function handlePaymentIntentSucceeded(StripeEvent $event): void
    {
        $paymentIntent = $event->data->object;
        $donationId = $paymentIntent->metadata->donation_id ?? null;

        if ($donationId === null) {
            return;
        }

        $donation = Donation::query()
            ->whereKey($donationId)
            ->where('status', DonationStatus::Pending)
            ->first();

        if ($donation === null) {
            return;
        }

        $cardBrand = null;
        $paymentMethodType = null;
        $stripeOptions = filled($event->account ?? null) ? ['stripe_account' => $event->account] : [];

        if ($paymentIntent->payment_method) {
            try {
                $paymentMethod = PaymentMethod::retrieve($paymentIntent->payment_method, $stripeOptions);
                $paymentMethodType = $paymentMethod->type;

                if ($paymentMethod->type === 'card' && $paymentMethod->card !== null) {
                    $cardBrand = $paymentMethod->card->brand;
                } else {
                    $cardBrand = $paymentMethodType;
                }
            } catch (\Exception $e) {
                // Payment method details are non-critical
            }
        }

        $charge = $paymentIntent->latest_charge ?? ($paymentIntent->charges->data[0] ?? null);
        $chargeId = is_string($charge) ? $charge : ($charge->id ?? null);
        $stripeFee = 0;
        $balanceTransaction = is_string($charge) ? null : ($charge->balance_transaction ?? null);

        if ($balanceTransaction) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $bt = BalanceTransaction::retrieve($balanceTransaction, $stripeOptions);
                $stripeFee = (float) ($bt->fee / 100);
            } catch (\Exception $e) {
                // If we can't retrieve the balance transaction, leave fee at 0
            }
        }

        $platformFee = round((float) $donation->gross_amount * 0.05, 2);

        $donation->update([
            'status' => DonationStatus::Succeeded,
            'stripe_charge_id' => $chargeId,
            'stripe_fee' => $stripeFee,
            'platform_fee' => $platformFee,
            'payment_method_brand' => $cardBrand,
            'payment_method_type' => $paymentMethodType,
            'net_amount' => (float) $donation->gross_amount - $stripeFee - $platformFee,
        ]);

        $donation->campaign()->increment('collected_amount', (float) $donation->gross_amount);

        if ($donation->type === DonationType::Recurring) {
            $subscription = app(CreateRecurringSubscription::class)->create($donation, $paymentIntent, $stripeOptions);
            $donation->update(['subscription_id' => $subscription->getKey()]);
            $subscription->increment('payment_count');
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

        $subscription->increment('payment_count');

        $grossAmount = (float) ($invoice->amount_paid / 100);
        $stripeFee = 0;
        $stripeOptions = filled($event->account ?? null) ? ['stripe_account' => $event->account] : [];

        if ($invoice->charge) {
            try {
                $chargeId = is_string($invoice->charge) ? $invoice->charge : $invoice->charge->id;
                $chargeObj = Charge::retrieve($chargeId, $stripeOptions);
                $balanceTransaction = $chargeObj->balance_transaction;

                if ($balanceTransaction) {
                    $btId = is_string($balanceTransaction) ? $balanceTransaction : $balanceTransaction->id;
                    $bt = BalanceTransaction::retrieve($btId, $stripeOptions);
                    $stripeFee = (float) ($bt->fee / 100);
                }
            } catch (\Exception $e) {
                // Fee retrieval is non-critical
            }
        }

        $platformFee = round($grossAmount * 0.05, 2);
        $netAmount = $grossAmount - $stripeFee - $platformFee;

        $donation = Donation::query()->create([
            'campaign_id' => $subscription->campaign_id,
            'donor_id' => $subscription->donor_id,
            'subscription_id' => $subscription->getKey(),
            'gross_amount' => $grossAmount,
            'stripe_fee' => $stripeFee,
            'platform_fee' => $platformFee,
            'net_amount' => $netAmount,
            'currency' => $invoice->currency,
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::Recurring,
            'stripe_payment_intent_id' => $invoice->payment_intent,
            'stripe_charge_id' => $invoice->charge,
        ]);

        $donation->campaign()->increment('collected_amount', $grossAmount);

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
