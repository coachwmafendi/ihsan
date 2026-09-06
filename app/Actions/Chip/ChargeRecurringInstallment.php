<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\ScheduleRetry;
use App\Services\SubscriptionActivityLogger;
use App\Services\SubscriptionSchedule;
use Carbon\CarbonImmutable;
use Chip\Builder\PurchaseBuilder;
use Chip\Exception\ChipApiException;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use RuntimeException;

final class ChargeRecurringInstallment
{
    public function __construct(private ScheduleRetry $scheduleRetry) {}

    public function handle(Subscription $subscription): void
    {
        $subscription->loadMissing(['campaign.organization', 'donor']);

        if (! $this->isDue($subscription)) {
            return;
        }

        $organization = $subscription->campaign?->organization;

        if ($organization === null) {
            throw new RuntimeException('Subscription is not linked to an organization.');
        }

        $donor = $subscription->donor;

        if ($donor === null) {
            throw new RuntimeException('Subscription is not linked to a donor.');
        }

        if (blank($subscription->chip_recurring_token)) {
            throw new RuntimeException('Subscription does not have a CHIP recurring token.');
        }

        try {
            $chip = ChipApiFactory::make($organization);
        } catch (InvalidArgumentException $e) {
            report($e);

            throw new RuntimeException('Failed to initialize CHIP client: '.$e->getMessage(), previous: $e);
        }

        $builder = PurchaseBuilder::create()
            ->brandId($organization->chip_brand_id)
            ->currency(strtoupper($subscription->currency))
            ->language('en')
            ->clientEmail($donor->email)
            ->clientFullName($donor->name)
            ->addProduct($subscription->campaign->title, (int) round((float) $subscription->amount * 100))
            ->paymentMethodWhitelist(PaymentMethodWhitelistMapper::cardOnly());

        if (Route::has('chip.webhook')) {
            $builder = $builder->successCallback(route('chip.webhook'));
        }

        $purchase = $builder->build();

        // CHIP has no idempotency key, so the schedule is the lock: the row is
        // pushed to its next period before the card is touched. A second worker
        // - or a retry of a job that died mid-charge - finds nothing due.
        if (! $this->claim($subscription)) {
            return;
        }

        try {
            $createdPurchase = $chip->purchases->create($purchase);
            $result = $chip->purchases->charge($createdPurchase->id, $subscription->chip_recurring_token);
        } catch (ChipApiException $e) {
            report($e);

            $this->releaseForRetry($subscription);

            throw new RuntimeException('Failed to charge CHIP recurring installment: '.$e->getMessage(), previous: $e);
        }

        $donation = $subscription->donations()->create([
            'campaign_id' => $subscription->campaign_id,
            'donor_id' => $subscription->donor_id,
            'currency' => $subscription->currency,
            'gross_amount' => $subscription->amount,
            'status' => $result->status === 'paid' ? DonationStatus::Succeeded : DonationStatus::Pending,
            'type' => DonationType::Recurring,
            'source' => $subscription->source ?? 'checkout_modal',
            'chip_purchase_id' => $result->id,
        ]);

        SubscriptionActivityLogger::installmentCreated($subscription, $donation, null, ['source' => 'system_automated']);

        if ($donation->status === DonationStatus::Succeeded) {
            SubscriptionActivityLogger::installmentCharged(
                $subscription,
                $donation,
                'chip',
                $result->id,
                null,
                ['source' => 'system_automated']
            );
        }

        $this->recordSuccess($subscription);

        app(SyncDonationDetails::class)->sync($donation);
    }

    private function isDue(Subscription $subscription): bool
    {
        if ($subscription->stripe_subscription_id !== null) {
            return false;
        }

        if ($subscription->status !== SubscriptionStatus::Active) {
            return false;
        }

        if ($subscription->next_charge_at !== null && $subscription->next_charge_at->isFuture()) {
            return false;
        }

        if ($subscription->paused_until !== null && $subscription->paused_until->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Take ownership of this installment by advancing the schedule.
     *
     * The update only lands while the row still carries the due date this call
     * read, so exactly one caller can claim a given installment.
     */
    private function claim(Subscription $subscription): bool
    {
        $dueAt = $subscription->next_charge_at;
        $now = CarbonImmutable::now();
        $nextChargeAt = SubscriptionSchedule::nextChargeAt($now, $subscription->interval);

        $claimed = Subscription::query()
            ->whereKey($subscription->getKey())
            ->where('status', SubscriptionStatus::Active)
            ->when(
                $dueAt === null,
                fn ($query) => $query->whereNull('next_charge_at'),
                fn ($query) => $query->where('next_charge_at', $dueAt),
            )
            ->update([
                'next_charge_at' => $nextChargeAt,
                'last_charge_attempt_at' => $now,
            ]);

        if ($claimed === 0) {
            return false;
        }

        $subscription->forceFill([
            'next_charge_at' => $nextChargeAt,
            'last_charge_attempt_at' => $now,
        ])->syncOriginal();

        return true;
    }

    /**
     * Hand the installment back to the retry schedule after a failed charge.
     */
    private function releaseForRetry(Subscription $subscription): void
    {
        $schedule = $this->scheduleRetry->afterFailure($subscription);

        $subscription->update([
            'status' => $schedule['status'],
            'retry_count' => $schedule['retry_count'],
            'failed_installment_count' => $schedule['failed_installment_count'],
            'next_charge_at' => $schedule['next_charge_at'],
        ]);
    }

    private function recordSuccess(Subscription $subscription): void
    {
        $now = CarbonImmutable::now();
        $schedule = $this->scheduleRetry->afterSuccess($subscription, $now);

        $subscription->update([
            'status' => $schedule['status'],
            'retry_count' => $schedule['retry_count'],
            'failed_installment_count' => $schedule['failed_installment_count'],
            'payment_count' => (int) $subscription->payment_count + 1,
            'last_charge_at' => $now,
            'last_charge_attempt_at' => $now,
            'next_charge_at' => $schedule['next_charge_at'],
            'current_period_start' => $now,
            'current_period_end' => $schedule['next_charge_at'],
        ]);
    }
}
