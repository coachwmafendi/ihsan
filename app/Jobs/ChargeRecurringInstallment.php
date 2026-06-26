<?php

namespace App\Jobs;

use App\Actions\Stripe\ChargeRecurringInstallment as ChargeAction;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class ChargeRecurringInstallment implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Subscription $subscription) {}

    public function handle(ChargeAction $action): void
    {
        $action->handle($this->subscription);
    }
}
