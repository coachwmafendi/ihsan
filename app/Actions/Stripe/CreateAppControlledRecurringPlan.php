<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use App\Services\SubscriptionSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\PaymentMethod;

class CreateAppControlledRecurringPlan
{
    /**
     * @param  array<string, string>  $stripeOptions
     */
    public function create(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions = []): Subscription
    {
        $lock = Cache::lock('create-recurring-subscription.'.$donation->getKey(), 60);

        try {
            $lock->block(30);
        } catch (LockTimeoutException) {
            $existingSubscription = $donation->fresh()?->subscription;

            if ($existingSubscription instanceof Subscription) {
                return $existingSubscription;
            }

            throw new \RuntimeException('Unable to acquire subscription creation lock for donation '.$donation->getKey());
        }

        try {
            return $this->createLocked($donation, $paymentIntent, $stripeOptions);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function createLocked(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions = []): Subscription
    {
        $donation->loadMissing('campaign.organization', 'donor');

        $donor = $donation->donor;

        if ($donor === null) {
            throw new \RuntimeException('Donation has no donor.');
        }

        $paymentMethodId = is_string($paymentIntent->payment_method ?? null)
            ? $paymentIntent->payment_method
            : ($paymentIntent->payment_method->id ?? null);

        if ($paymentMethodId === null) {
            throw new \RuntimeException('PaymentIntent has no payment method.');
        }

        $customerId = $this->resolveOrCreateCustomer($donation, $paymentIntent, $stripeOptions);

        $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);
        $paymentMethod->attach(['customer' => $customerId], $stripeOptions);

        $donorPaymentMethod = $this->syncDonorPaymentMethod($donor, $paymentMethod);

        return DB::transaction(function () use (
            $donation,
            $donorPaymentMethod,
        ): Subscription {
            $freshDonation = Donation::query()->whereKey($donation->getKey())->lockForUpdate()->firstOrFail();

            $existing = $freshDonation->subscription;
            if ($existing !== null) {
                return $existing;
            }

            $interval = SubscriptionInterval::Monthly;
            $now = now();
            $nextChargeAt = SubscriptionSchedule::nextChargeAt(CarbonImmutable::instance($now), $interval);

            $subscription = Subscription::query()->create([
                'campaign_id' => $freshDonation->campaign_id,
                'donor_id' => $freshDonation->donor_id,
                'donor_payment_method_id' => $donorPaymentMethod->getKey(),
                'source' => $freshDonation->source,
                'amount' => (float) $freshDonation->gross_amount,
                'currency' => $freshDonation->currency,
                'interval' => $interval,
                'status' => SubscriptionStatus::Active,
                'cover_fee' => (float) ($freshDonation->donor_fee_covered ?? 0) > 0,
                'fee_cover_amount' => (float) ($freshDonation->donor_fee_covered ?? 0) > 0 ? (float) $freshDonation->donor_fee_covered : null,
                'current_period_start' => $now,
                'current_period_end' => $nextChargeAt,
                'next_charge_at' => $nextChargeAt,
                'payment_count' => 1,
                'failed_installment_count' => 0,
                'retry_count' => 0,
            ]);

            $freshDonation->update(['subscription_id' => $subscription->getKey()]);

            return $subscription;
        });
    }

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function resolveOrCreateCustomer(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions): string
    {
        $donation->loadMissing('campaign.organization', 'donor');
        $organization = $donation->campaign?->organization;
        $donor = $donation->donor;

        if ($donor === null || $organization === null) {
            throw new \RuntimeException('Donation is not linked to a donor and organization.');
        }

        $customerId = app(ResolveDonorStripeCustomer::class)
            ->resolve($donor, $organization, 'app_controlled_recurring');

        if (($paymentIntent->customer ?? null) !== $customerId) {
            StripePaymentIntent::update($paymentIntent->id, ['customer' => $customerId], $stripeOptions);
        }

        return $customerId;
    }

    private function syncDonorPaymentMethod(Donor $donor, PaymentMethod $paymentMethod): DonorPaymentMethod
    {
        if ($paymentMethod->type !== 'card' || $paymentMethod->card === null) {
            throw new \RuntimeException('Only card payment methods are supported for recurring plans.');
        }

        $card = $paymentMethod->card;

        DonorPaymentMethod::query()->where('donor_id', $donor->getKey())->update(['is_default' => false]);

        return DonorPaymentMethod::updateOrCreate(
            ['stripe_payment_method_id' => $paymentMethod->id],
            [
                'donor_id' => $donor->getKey(),
                'brand' => ucfirst((string) $card->brand),
                'last4' => $card->last4,
                'exp_month' => $card->exp_month,
                'exp_year' => $card->exp_year,
                'country' => $card->country ?? null,
                'is_default' => true,
            ],
        );
    }
}
