<?php

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Actions\Stripe\CreateRecurringSubscription;
use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Mail\DonorDunningNotification;
use App\Mail\PlatformInvoicePaid;
use App\Models\Donation;
use App\Models\Fraud\BlockedDonation;
use App\Models\Fraud\FraudAttempt;
use App\Models\MonthlyInvoice;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\Subscription;
use App\Models\WebhookLog;
use App\Services\FraudDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Stripe\Event as StripeEvent;
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
            'charge.refunded' => $this->handleChargeRefunded($event),
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

        $donation = Donation::query()->whereKey($donationId)->first();

        if ($donation === null) {
            return;
        }

        $wasPending = $donation->status === DonationStatus::Pending;
        $stripeOptions = filled($event->account ?? null) ? ['stripe_account' => $event->account] : [];

        $synced = app(SyncDonationStripeDetails::class)->sync(
            donation: $donation,
            paymentIntent: $stripeOptions === [] ? $paymentIntent : null,
            stripeOptions: $stripeOptions,
        );
        $paymentIntent = $synced['payment_intent'];

        $donation->update([
            'status' => DonationStatus::Succeeded,
        ]);

        $donation->refresh();

        if ($wasPending) {
            $campaign = $donation->campaign;
            $previousCollected = (float) $campaign->collected_amount;
            $incrementAmount = (float) ($donation->base_amount ?? $donation->gross_amount);
            $campaign->increment('collected_amount', $incrementAmount);
            $campaign->refresh();

            SendCampaignMilestoneNotification::dispatch($campaign, $previousCollected);
        }

        if ($donation->type === DonationType::Recurring && ($wasPending || $donation->subscription_id === null)) {
            $subscription = app(CreateRecurringSubscription::class)->create($donation, $paymentIntent, $stripeOptions);
            $donation->update(['subscription_id' => $subscription->getKey()]);

            if ($wasPending) {
                $subscription->increment('payment_count');
            }

            SendNewSubscriptionNotification::dispatch($donation);
        }

        if ($wasPending) {
            SendDonationReceipt::dispatch($donation);

            if ($donation->type !== DonationType::Recurring) {
                SendNewDonationNotification::dispatch($donation);
            }

            SendLargeDonationNotification::dispatch($donation);
            SendMetaConversionEvent::dispatch($donation);
            SendLinkedInConversionEvent::dispatch($donation);
            SendXAdsConversionEvent::dispatch($donation);
            SendSnapchatConversionEvent::dispatch($donation);
        }

        SyncDonationStripeDetailsJob::dispatch($donation->getKey())->delay(now()->addMinutes(2));
    }

    private function handleInvoicePaid(StripeEvent $event): void
    {
        $invoice = $event->data->object;
        $metadata = $invoice->metadata ?? [];

        if (($metadata['type'] ?? null) === 'processing_fees') {
            $this->handlePlatformInvoicePaid($event);
        } else {
            $this->handleDonorInvoicePaid($event);
        }
    }

    private function handlePaymentIntentFailed(StripeEvent $event): void
    {
        $paymentIntent = $event->data->object;
        $donationId = $paymentIntent->metadata->donation_id ?? null;

        if ($donationId === null) {
            return;
        }

        $donation = Donation::query()->whereKey($donationId)->first();

        if ($donation === null) {
            return;
        }

        $donation->update([
            'status' => DonationStatus::Failed,
        ]);

        $donation->loadMissing('subscription.donor', 'subscription.campaign.organization');

        if ($donation->subscription !== null) {
            SendFailedPaymentNotification::dispatch($donation->subscription);
        }
    }

    private function handleDonorInvoicePaid(StripeEvent $event): void
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

        $subscription->loadMissing('campaign.organization');

        $invoiceTotal = (float) (($invoice->amount_paid ?? 0) / 100);

        if ($invoiceTotal <= 0) {
            return;
        }

        $feeCoverAmount = (float) ($subscription->fee_cover_amount ?? 0);

        if ($feeCoverAmount > 0) {
            $grossAmount = (float) $subscription->amount;
            $donorFeeCovered = max(0, $invoiceTotal - $grossAmount);
        } else {
            $grossAmount = $invoiceTotal;
            $donorFeeCovered = 0.0;
        }

        $existingDonation = Donation::query()
            ->where(function ($query) use ($invoice): void {
                $hasInvoiceId = ! empty($invoice->id);

                if ($hasInvoiceId) {
                    $query->where('stripe_invoice_id', $invoice->id);
                }

                if (! empty($invoice->payment_intent)) {
                    $method = $hasInvoiceId ? 'orWhere' : 'where';

                    $query->{$method}('stripe_payment_intent_id', $invoice->payment_intent);
                }
            })
            ->first();

        if ($existingDonation !== null) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'retry_count' => 0,
            'current_period_start' => now()->setTimestamp($invoice->period_start ?? $invoice->created),
            'current_period_end' => now()->setTimestamp($invoice->period_end),
        ]);

        $subscription->increment('payment_count');

        $stripeOptions = filled($event->account ?? null) ? ['stripe_account' => $event->account] : [];
        $campaign = $subscription->campaign;

        $donation = Donation::query()->create([
            'campaign_id' => $subscription->campaign_id,
            'donor_id' => $subscription->donor_id,
            'subscription_id' => $subscription->getKey(),
            'gross_amount' => $grossAmount,
            'donor_fee_covered' => $donorFeeCovered,
            'stripe_fee' => 0,
            'processing_fee' => 0,
            'net_amount' => $grossAmount,
            'currency' => $invoice->currency,
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::Recurring,
            'stripe_payment_intent_id' => $invoice->payment_intent,
            'stripe_charge_id' => $invoice->charge,
            'stripe_invoice_id' => $invoice->id,
        ]);

        app(SyncDonationStripeDetails::class)->sync($donation, null, $stripeOptions);

        $donation->refresh();

        if ($donation->fraud_status !== 'blocked' && $donation->risk_score !== null) {
            $riskThreshold = (int) ($campaign->organization?->settings['risk_score_threshold'] ?? 80);

            if ($donation->risk_score >= $riskThreshold) {
                BlockedDonation::create([
                    'donation_id' => $donation->id,
                    'reason' => "Stripe Radar risk score: {$donation->risk_score}",
                    'review_status' => 'pending',
                ]);

                $donation->update([
                    'fraud_status' => 'blocked',
                    'status' => DonationStatus::Refunded, // atau failed
                ]);

                FraudAttempt::create([
                    'donor_id' => $donation->donor_id,
                    'email' => $donation->donor?->email,
                    'amount' => $donation->gross_amount,
                    'currency' => $donation->currency,
                    'reason' => 'Stripe Radar high risk score',
                    'action' => 'blocked',
                    'metadata' => ['risk_score' => $donation->risk_score],
                ]);

                FraudDetectionService::notifyAdmins(
                    $donation,
                    "Stripe Radar risk score: {$donation->risk_score}",
                    'blocked'
                );

                return;
            }
        }

        $previousCollected = (float) $campaign->collected_amount;
        $incrementAmount = (float) ($donation->base_amount ?? $grossAmount);
        $campaign->increment('collected_amount', $incrementAmount);
        $campaign->refresh();

        SendCampaignMilestoneNotification::dispatch($campaign, $previousCollected);

        SendDonationReceipt::dispatch($donation);
        SendNewDonationNotification::dispatch($donation);
        SendLargeDonationNotification::dispatch($donation);
        SendMetaConversionEvent::dispatch($donation);
        SendLinkedInConversionEvent::dispatch($donation);
        SendXAdsConversionEvent::dispatch($donation);
        SendSnapchatConversionEvent::dispatch($donation);
        SyncDonationStripeDetailsJob::dispatch($donation->getKey())->delay(now()->addMinutes(2));
    }

    private function handlePlatformInvoicePaid(StripeEvent $event): void
    {
        $invoice = $event->data->object;
        $metadata = $invoice->metadata ?? [];

        $organizationId = $metadata['organization_id'] ?? null;

        if ($organizationId === null) {
            return;
        }

        $monthlyInvoice = MonthlyInvoice::query()
            ->with('organization')
            ->where('stripe_invoice_id', $invoice->id)
            ->first();

        if ($monthlyInvoice === null) {
            return;
        }

        $monthlyInvoice->update([
            'stripe_status' => $invoice->status,
            'paid_at' => now(),
        ]);

        ProcessingFee::query()
            ->where('monthly_invoice_id', $monthlyInvoice->id)
            ->update(['status' => 'paid']);

        if ($monthlyInvoice->organization?->contact_email) {
            Mail::to($monthlyInvoice->organization->contact_email)
                ->queue(new PlatformInvoicePaid($monthlyInvoice));
        }
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

        $retryCount = $invoice->attempt_count ?? ($subscription->retry_count + 1);

        $subscription->update([
            'status' => SubscriptionStatus::PastDue,
            'retry_count' => $retryCount,
        ]);

        $subscription->loadMissing(['donor', 'campaign.organization']);

        $is27th = now()->day === 27;
        $isFinalAttempt = $is27th && $retryCount >= 4;

        if ($retryCount === 1 || $retryCount === 2 || $retryCount === 3 || $isFinalAttempt) {
            if ($subscription->donor?->hasOptedOutOfEmails()) {
                return;
            }

            $mailable = new DonorDunningNotification($subscription, $retryCount, $isFinalAttempt);

            Mail::to($subscription->donor->email)
                ->queue($mailable);

            app(LogDonorEmail::class)->handle(
                donor: $subscription->donor,
                mailable: $mailable,
                organization: $subscription->campaign?->organization,
                subscription: $subscription,
                metadata: [
                    'retry_count' => $retryCount,
                    'is_final_attempt' => $isFinalAttempt,
                ],
            );
        }

        if ($invoice->next_payment_attempt === null) {
            SendFailedPaymentNotification::dispatch($subscription);
        }
    }

    private function handleSubscriptionDeleted(StripeEvent $event): void
    {
        $stripeSubscription = $event->data->object;

        $subscription = Subscription::query()
            ->where('stripe_subscription_id', $stripeSubscription->id)
            ->first();

        if ($subscription === null) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        SendSubscriptionCancelledNotification::dispatch($subscription);
    }

    private function handleChargeRefunded(StripeEvent $event): void
    {
        $charge = $event->data->object;
        $paymentIntentId = $charge->payment_intent;

        if ($paymentIntentId === null) {
            return;
        }

        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($donation === null) {
            return;
        }

        $donation->loadMissing('campaign');
        $campaign = $donation->campaign;

        $donation->update([
            'status' => DonationStatus::Refunded,
            'refunded_at' => now(),
        ]);

        if ($campaign !== null) {
            $decrementAmount = (float) ($donation->base_amount ?? $donation->gross_amount);
            $newAmount = max(0, (float) $campaign->collected_amount - $decrementAmount);
            $campaign->update(['collected_amount' => $newAmount]);
        }

        SendRefundNotification::dispatch($donation);
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

        $organization = Organization::query()
            ->where('stripe_account_id', $account->id)
            ->first();

        if (! $organization) {
            return;
        }

        $updates = ['stripe_onboarded' => $account->charges_enabled];

        if ($account->charges_enabled && $organization->stripe_onboarded_at === null) {
            $updates['stripe_onboarded_at'] = now();
        }

        $organization->update($updates);
    }
}
