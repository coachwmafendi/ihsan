<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Stripe\SyncPayout;
use App\Models\Organization;
use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\StripeClient;

class SyncPayouts extends Command
{
    protected $signature = 'app:sync-payouts {--organization= : Organization public_id} {--days=90}';

    protected $description = 'Sync Stripe payouts for organizations';

    public function handle(SyncPayout $syncPayout): int
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $organizations = $this->option('organization')
            ? Organization::query()->where('public_id', $this->option('organization'))->get()
            : Organization::query()->whereNotNull('stripe_account_id')->get();

        $days = (int) $this->option('days');
        $createdAfter = now()->subDays($days)->startOfDay()->timestamp;

        foreach ($organizations as $organization) {
            if (blank($organization->stripe_account_id)) {
                continue;
            }

            $stripe = new StripeClient(config('services.stripe.secret'));
            $payouts = $stripe->payouts->all(
                [
                    'created' => ['gte' => $createdAfter],
                    'limit' => 100,
                    'expand' => ['data.destination'],
                ],
                ['stripe_account' => $organization->stripe_account_id]
            );

            foreach ($payouts->autoPagingIterator() as $payout) {
                $syncPayout->sync($organization, $payout->toArray());
            }
        }

        return self::SUCCESS;
    }
}
