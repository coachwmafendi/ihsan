<?php

namespace App\Jobs;

use App\Actions\Chip\ChargeRecurringInstallment as ChargeChipInstallment;
use App\Actions\Stripe\ChargeRecurringInstallment as ChargeStripeInstallment;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class ChargeRecurringInstallment implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function handle(ChargeStripeInstallment $stripeAction, ChargeChipInstallment $chipAction): void
    {
        $this->subscription->loadMissing('campaign.organization');

        if (filled($this->subscription->chip_recurring_token)) {
            $chipAction->handle($this->subscription);
            return;
        }

        $stripeAction->handle($this->subscription);
    }
}
