<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Stripe\RegisterPaymentMethodDomains;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegisterStripePaymentMethodDomains implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public int $organizationId) {}

    public function handle(RegisterPaymentMethodDomains $action): void
    {
        $organization = Organization::find($this->organizationId);

        if (! $organization || blank($organization->stripe_account_id)) {
            return;
        }

        $action->register($organization);
    }
}
