<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Jobs\ChargeRecurringInstallment;
use App\Models\Subscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('ihsan:charge-recurring-plans {--chunk=100}')]
#[Description('Dispatch jobs to charge due app-controlled recurring plans')]
class ChargeDueRecurringPlans extends Command
{
    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');

        $query = Subscription::query()
            ->whereNull('stripe_subscription_id')
            ->where('status', SubscriptionStatus::Active)
            ->where('next_charge_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('paused_until')
                    ->orWhere('paused_until', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('cancel_at')
                    ->orWhere('cancel_at', '>=', now());
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No due recurring plans found.');

            return Command::SUCCESS;
        }

        $this->info("Dispatching ChargeRecurringInstallment jobs for {$count} due subscription(s).");

        $query->chunkById($chunk, function ($subscriptions): void {
            foreach ($subscriptions as $subscription) {
                ChargeRecurringInstallment::dispatch($subscription);
            }
        });

        $this->info('Done.');

        Cache::put('recurring_plans:last_run_at', now(), now()->addDay());

        return Command::SUCCESS;
    }
}
