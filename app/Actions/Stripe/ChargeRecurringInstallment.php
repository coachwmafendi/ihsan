<?php

declare(strict_types=1);

namespace App\Actions\Stripe;

use App\Data\ChargeResult;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Jobs\SendDonorDunningNotification;
use App\Jobs\SendDonorRecurringPaymentNotification;
use App\Jobs\SendFailedPaymentNotification;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendLinkedInConversionEvent;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendSnapchatConversionEvent;
use App\Jobs\SendXAdsConversionEvent;
use App\Jobs\SyncDonationStripeDetailsJob;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\ScheduleRetry;
use App\Services\StripeMetadata;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Stripe;
use Throwable;

class ChargeRecurringInstallment
{
    public function __construct(
        private ScheduleRetry $scheduleRetry,
        private SyncDonationStripeDetails $syncDonationStripeDetails,
    ) {}

    public function handle(Subscription $subscription): ChargeResult
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing(['campaign.organization', 'donor', 'donorPaymentMethod']);

        $guardResult = $this->guardSubscriptionState($subscription);

        if ($guardResult !== null) {
            return $guardResult;
        }

        $campaign = $subscription->campaign;
        $organization = $campaign?->organization;

        if ($campaign === null || $organization === null || ! $this->organizationIsOnboarded($organization)) {
            $subscription->update(['status' => SubscriptionStatus::Failed, 'next_charge_at' => null]);

            return new ChargeResult('failed', errorCode: 'organization_not_onboarded');
        }

        $donor = $subscription->donor;

        if ($donor === null || blank($donor->stripe_customer_id)) {
            $subscription->update(['status' => SubscriptionStatus::Failed, 'next_charge_at' => null]);

            return new ChargeResult('failed', errorCode: 'missing_customer');
        }

        $paymentMethod = $subscription->donorPaymentMethod;

        if ($paymentMethod === null || blank($paymentMethod->stripe_payment_method_id)) {
            $subscription->update(['status' => SubscriptionStatus::Failed, 'next_charge_at' => null]);

            return new ChargeResult('failed', errorCode: 'missing_payment_method');
        }

        $stripeOptions = ['stripe_account' => $organization->stripe_account_id];

        $grossAmount = (float) $subscription->amount;
        $feeCoverAmount = $subscription->cover_fee ? (float) ($subscription->fee_cover_amount ?? 0) : 0.0;
        $totalCents = (int) round(($grossAmount + $feeCoverAmount) * 100);

        $params = [
            'amount' => $totalCents,
            'currency' => strtolower($subscription->currency),
            'customer' => $donor->stripe_customer_id,
            'payment_method' => $paymentMethod->stripe_payment_method_id,
            'off_session' => true,
            'confirm' => true,
            'setup_future_usage' => 'off_session',
            'receipt_email' => $donor->email,
            'description' => (string) str($campaign->title)->limit(200),
            'metadata' => $this->metadata($subscription, $campaign, $donor),
        ];

        $stripeOptions['idempotency_key'] = $this->idempotencyKey($subscription);

        if ($organization->fee_collection_method === 'upfront') {
            $feePercent = (float) config('services.stripe.processing_fee_percent', 2.5);
            $params['application_fee_amount'] = (int) round($totalCents * $feePercent / 100);
            $params['metadata'][StripeMetadata::key('platform_fee_amount')] = (string) $params['application_fee_amount'];
        }

        try {
            $paymentIntent = StripePaymentIntent::create($params, $stripeOptions);
        } catch (CardException $e) {
            $oldRetryCount = $this->recordAttemptFailure($subscription);
            $this->dispatchFailureNotifications($subscription, $e->getMessage(), $oldRetryCount, true);

            return new ChargeResult('failed', errorCode: $e->getDeclineCode() ?? 'card_declined');
        } catch (Throwable $e) {
            report($e);

            $oldRetryCount = $this->recordAttemptFailure($subscription);
            $this->dispatchFailureNotifications($subscription, $e->getMessage(), $oldRetryCount, false);

            return new ChargeResult('failed', errorCode: 'stripe_exception');
        }

        return match ($paymentIntent->status) {
            'succeeded' => $this->handleSuccess($subscription, $campaign, $donor, $paymentIntent, $stripeOptions),
            'requires_action' => $this->handleRequiresAction($subscription, $paymentIntent),
            default => $this->handleFailure($subscription, $paymentIntent),
        };
    }

    private function organizationIsOnboarded(Organization $organization): bool
    {
        return filled($organization->stripe_account_id) && $organization->stripe_active;
    }

    private function guardSubscriptionState(Subscription $subscription): ?ChargeResult
    {
        if ($subscription->stripe_subscription_id !== null) {
            return new ChargeResult('failed', errorCode: 'not_app_controlled');
        }

        if ($subscription->status !== SubscriptionStatus::Active) {
            return new ChargeResult('failed', errorCode: 'subscription_not_active');
        }

        if ($subscription->next_charge_at !== null && $subscription->next_charge_at->isFuture()) {
            return new ChargeResult('failed', errorCode: 'next_charge_in_future');
        }

        if ($subscription->paused_until !== null && $subscription->paused_until->isFuture()) {
            return new ChargeResult('failed', errorCode: 'subscription_paused');
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function metadata(Subscription $subscription, Campaign $campaign, Donor $donor): array
    {
        return [
            StripeMetadata::key('subscription_id') => (string) $subscription->getKey(),
            StripeMetadata::key('subscription_public_id') => $subscription->public_id ?? '',
            StripeMetadata::key('donor_id') => (string) $donor->getKey(),
            StripeMetadata::key('donor_public_id') => $donor->public_id ?? '',
            StripeMetadata::key('campaign_id') => (string) $campaign->getKey(),
            StripeMetadata::key('campaign_public_id') => $campaign->public_id ?? '',
            StripeMetadata::key('is_recurring_installment') => 'true',
            StripeMetadata::key('source') => $subscription->source ?? 'checkout_modal',
            StripeMetadata::key('environment') => config('app.env'),
        ];
    }

    private function idempotencyKey(Subscription $subscription): string
    {
        $nextChargeAt = $subscription->next_charge_at?->toIso8601String() ?? 'none';

        return "subscription:{$subscription->getKey()}:{$nextChargeAt}:{$subscription->retry_count}";
    }

    private function handleSuccess(
        Subscription $subscription,
        Campaign $campaign,
        Donor $donor,
        StripePaymentIntent $paymentIntent,
        array $stripeOptions,
    ): ChargeResult {
        $existingDonation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntent->id)
            ->first();

        if ($existingDonation !== null) {
            return new ChargeResult('succeeded', $existingDonation);
        }

        $grossAmount = (float) $subscription->amount;
        $feeCoverAmount = $subscription->cover_fee ? (float) ($subscription->fee_cover_amount ?? 0) : 0.0;
        $now = now();

        [$donation, $previousCollected] = DB::transaction(function () use (
            $subscription,
            $campaign,
            $donor,
            $paymentIntent,
            $grossAmount,
            $feeCoverAmount,
            $now,
        ): array {
            $campaign->refresh();
            $previousCollected = (float) $campaign->collected_amount;

            $donation = Donation::query()->create([
                'campaign_id' => $campaign->getKey(),
                'donor_id' => $donor->getKey(),
                'subscription_id' => $subscription->getKey(),
                'stripe_payment_intent_id' => $paymentIntent->id,
                'gross_amount' => $grossAmount,
                'donor_fee_covered' => $feeCoverAmount,
                'currency' => $subscription->currency,
                'status' => DonationStatus::Succeeded,
                'type' => DonationType::Recurring,
                'source' => $subscription->source ?? 'checkout_modal',
            ]);

            $incrementAmount = $grossAmount;
            $campaign->increment('collected_amount', $incrementAmount);

            $schedule = $this->scheduleRetry->afterSuccess(
                $subscription,
                CarbonImmutable::instance($now),
            );

            $subscription->update([
                'status' => $schedule['status'],
                'retry_count' => $schedule['retry_count'],
                'failed_installment_count' => $schedule['failed_installment_count'],
                'payment_count' => $subscription->payment_count + 1,
                'last_charge_at' => $now,
                'last_charge_attempt_at' => $now,
                'next_charge_at' => $schedule['next_charge_at'],
                'current_period_start' => $now,
                'current_period_end' => $schedule['next_charge_at'],
            ]);

            return [$donation, $previousCollected];
        });

        try {
            $this->syncDonationStripeDetails->sync($donation, $paymentIntent, $stripeOptions);
            $donation->refresh();
        } catch (Throwable $syncException) {
            report($syncException);
            SyncDonationStripeDetailsJob::dispatch($donation->getKey())->delay(now()->addMinutes(2));
        }

        SendCampaignMilestoneNotification::dispatch($campaign, $previousCollected);
        SendNewDonationNotification::dispatch($donation);
        SendDonorRecurringPaymentNotification::dispatch($donation);
        SendLargeDonationNotification::dispatch($donation);
        SendMetaConversionEvent::dispatch($donation);
        SendLinkedInConversionEvent::dispatch($donation);
        SendXAdsConversionEvent::dispatch($donation);
        SendSnapchatConversionEvent::dispatch($donation);

        return new ChargeResult('succeeded', $donation);
    }

    private function handleRequiresAction(Subscription $subscription, StripePaymentIntent $paymentIntent): ChargeResult
    {
        $subscription->update([
            'last_charge_attempt_at' => now(),
            'next_charge_at' => now()->addDay(),
            'status' => 'past_due',
        ]);

        return new ChargeResult('requires_action', clientSecret: $paymentIntent->client_secret);
    }

    private function handleFailure(Subscription $subscription, StripePaymentIntent $paymentIntent): ChargeResult
    {
        $errorCode = $paymentIntent->last_payment_error?->code ?? $paymentIntent->status;
        $errorMessage = $paymentIntent->last_payment_error?->message ?? null;

        $oldRetryCount = $this->recordAttemptFailure($subscription);
        $this->dispatchFailureNotifications($subscription, $errorMessage, $oldRetryCount, true);

        return new ChargeResult('failed', errorCode: (string) $errorCode);
    }

    private function recordAttemptFailure(Subscription $subscription): int
    {
        $oldRetryCount = $subscription->retry_count;
        $schedule = $this->scheduleRetry->afterFailure($subscription);

        $subscription->update([
            'status' => $schedule['status'],
            'retry_count' => $schedule['retry_count'],
            'failed_installment_count' => $schedule['failed_installment_count'],
            'last_charge_attempt_at' => now(),
            'next_charge_at' => $schedule['status'] === 'failed' ? null : $schedule['next_charge_at'],
        ]);

        return $oldRetryCount;
    }

    private function dispatchFailureNotifications(Subscription $subscription, ?string $errorMessage, int $oldRetryCount, bool $sendDunning): void
    {
        SendFailedPaymentNotification::dispatch($subscription, $errorMessage);

        if ($sendDunning && $subscription->donor?->canReceiveEmails()) {
            $isFinalAttempt = $subscription->status === SubscriptionStatus::Failed;
            SendDonorDunningNotification::dispatch($subscription, $oldRetryCount + 1, $isFinalAttempt);
        }
    }
}
