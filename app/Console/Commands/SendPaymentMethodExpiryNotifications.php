<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonorPaymentMethodExpiringNotification;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('ihsan:send-payment-method-expiry-notifications {--dry-run} {--chunk=100}')]
#[Description('Send email reminders to supporters whose card is about to expire')]
class SendPaymentMethodExpiryNotifications extends Command
{
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');

        $query = Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->where('cancel_at_period_end', false)
            ->whereNull('cancelled_at')
            ->whereNull('paused_until')
            ->whereNull('chip_recurring_token')
            ->whereNotNull('donor_payment_method_id')
            ->whereHas('donorPaymentMethod', function ($query) {
                $query->whereNotNull('exp_month')
                    ->whereNotNull('exp_year');
            })
            ->with(['donor', 'campaign.organization', 'donorPaymentMethod']);

        $total = (clone $query)->count();

        $this->info("Checking {$total} subscription(s) for expiring payment methods...");

        $dispatched = 0;
        $skipped = 0;

        if ($total === 0) {
            $this->info("Done. Dispatched: {$dispatched}, Skipped: {$skipped}.");

            return self::SUCCESS;
        }

        $query->chunkById($chunk, function ($subscriptions) use ($dryRun, &$dispatched, &$skipped): void {
            $grouped = $this->groupByPaymentMethod($subscriptions);

            foreach ($grouped as $group) {
                $subscriptionIds = $group['ids'];
                $daysRemaining = $group['days_remaining'];

                if ($daysRemaining === null || empty($subscriptionIds)) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line('[dry-run] Would notify '.count($subscriptionIds).' subscription(s) for payment method '.$group['payment_method_id'].' (expires in '.$daysRemaining.' days)');
                    $dispatched++;

                    continue;
                }

                SendDonorPaymentMethodExpiringNotification::dispatch($subscriptionIds, $daysRemaining);
                $dispatched++;
            }
        });

        $this->info("Done. Dispatched: {$dispatched}, Skipped: {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * Group subscriptions by donor_payment_method_id and determine the
     * notification window for each group.
     *
     * @param  Collection<int, Subscription>  $subscriptions
     * @return array<int, array{payment_method_id: int, ids: array<int>, days_remaining: int|null}>
     */
    private function groupByPaymentMethod($subscriptions): array
    {
        $groups = [];

        foreach ($subscriptions as $subscription) {
            $paymentMethod = $subscription->donorPaymentMethod;

            if ($paymentMethod === null) {
                continue;
            }

            $daysRemaining = $this->daysUntilExpiry($paymentMethod->expiresAt());

            if ($daysRemaining === null) {
                continue;
            }

            $paymentMethodId = (int) $paymentMethod->getKey();

            if (! isset($groups[$paymentMethodId])) {
                $groups[$paymentMethodId] = [
                    'payment_method_id' => $paymentMethodId,
                    'ids' => [],
                    'days_remaining' => $daysRemaining,
                ];
            }

            $groups[$paymentMethodId]['ids'][] = (int) $subscription->getKey();

            // If any subscription in the group is within 7 days, promote the
            // whole group to the 7-day notification.
            if ($daysRemaining === 7 || $groups[$paymentMethodId]['days_remaining'] === 7) {
                $groups[$paymentMethodId]['days_remaining'] = 7;
            }
        }

        return $groups;
    }

    private function daysUntilExpiry(?CarbonImmutable $expiresAt): ?int
    {
        if ($expiresAt === null || $expiresAt->isPast()) {
            return null;
        }

        $daysRemaining = (int) CarbonImmutable::now()->startOfDay()->diffInDays($expiresAt->startOfDay(), false);

        if ($daysRemaining <= 7) {
            return 7;
        }

        if ($daysRemaining <= 30) {
            return 30;
        }

        return null;
    }
}
