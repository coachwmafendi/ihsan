<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendDonorNewSubscriptionNotification;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendLinkedInConversionEvent;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendNewSubscriptionNotification;
use App\Jobs\SendSnapchatConversionEvent;
use App\Jobs\SendXAdsConversionEvent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class FinalizeDonation
{
    public function __construct(
        private SyncDonationDetails $sync,
        private CreateRecurringPlan $recurringPlan,
    ) {}

    public function finalize(Donation $donation): void
    {
        $donation->loadMissing('campaign.organization');

        $organization = $donation->campaign?->organization;

        if (! $organization instanceof Organization) {
            throw new RuntimeException('Donation is not linked to an organization.');
        }

        if (! $organization->chip_onboarded) {
            throw new RuntimeException('Organization is not CHIP onboarded.');
        }

        $this->sync->sync($donation);
        $donation->refresh();

        if ($donation->status !== DonationStatus::Succeeded) {
            throw new RuntimeException('CHIP purchase is not completed.');
        }

        $wasAlreadyFinalized = DB::transaction(function () use ($donation): bool {
            $lockedDonation = Donation::query()->whereKey($donation->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedDonation->finalized_at !== null) {
                return true;
            }

            $lockedDonation->update(['finalized_at' => now()]);

            $campaign = Campaign::query()->whereKey($lockedDonation->campaign_id)->lockForUpdate()->first();

            if ($campaign !== null) {
                $campaign->increment('collected_amount', (float) ($lockedDonation->base_amount ?? $lockedDonation->gross_amount));
            }

            return false;
        });

        // The donor's browser, the return URL and the webhook all finalize the
        // same donation, so stop here rather than queueing the receipt, the
        // notifications and the conversion events a second time.
        if ($wasAlreadyFinalized) {
            return;
        }

        $donation->refresh();

        if ($donation->type === DonationType::Recurring && $donation->subscription_id === null) {
            if (blank($donation->chip_recurring_token)) {
                throw new RuntimeException('Recurring donation is missing CHIP recurring token.');
            }

            $subscription = $this->recurringPlan->create($donation, $donation->chip_recurring_token);

            if ($subscription->wasRecentlyCreated) {
                SendNewSubscriptionNotification::dispatch($donation);
                SendDonorNewSubscriptionNotification::dispatch($donation);
            }
        }

        if ($donation->type !== DonationType::Recurring) {
            SendDonationReceipt::dispatch($donation);
            SendNewDonationNotification::dispatch($donation);
            SendLargeDonationNotification::dispatch($donation);
            SendMetaConversionEvent::dispatch($donation);
            SendLinkedInConversionEvent::dispatch($donation);
            SendXAdsConversionEvent::dispatch($donation);
            SendSnapchatConversionEvent::dispatch($donation);
        }
    }
}
