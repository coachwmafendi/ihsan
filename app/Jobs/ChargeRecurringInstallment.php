<?php

namespace App\Jobs;

use App\Actions\Chip\ChargeRecurringInstallment as ChargeChipAction;
use App\Actions\Stripe\ChargeRecurringInstallment as ChargeStripeAction;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class ChargeRecurringInstallment implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function handle(): void
    {
        if (filled($this->subscription->stripe_subscription_id)) {
            app(ChargeStripeAction::class)->handle($this->subscription);

            return;
        }

        if (filled($this->subscription->chip_recurring_token)) {
            app(ChargeChipAction::class)->handle($this->subscription);

            return;
        }

        // No supported gateway token; nothing to charge.
    }
}
