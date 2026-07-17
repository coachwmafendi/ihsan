<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Models\Donation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Stripe\Stripe;

#[Signature('app:backfill-donation-payment-method-expiry {--dry-run} {--chunk=100} {--limit=}')]
#[Description('Backfill payment method expiry dates for card donations from Stripe')]
class BackfillDonationPaymentMethodExpiry extends Command
{
    public function handle(SyncDonationStripeDetails $sync): int
    {
        $dryRun = $this->option('dry-run');
        $chunk = (int) $this->option('chunk');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        Stripe::setApiKey(config('services.stripe.secret'));

        $query = Donation::query()
            ->whereNotNull('stripe_payment_intent_id')
            ->whereNotNull('payment_method_brand')
            ->whereNotNull('payment_method_last4')
            ->whereNull('payment_method_exp_month')
            ->whereNull('payment_method_exp_year');

        $total = (clone $query)->count();
        $total = $limit !== null ? min($total, $limit) : $total;

        if ($total === 0) {
            $this->info('No donations need expiry backfill.');

            return self::SUCCESS;
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $this->info("Backfilling expiry for {$total} donation(s)...");

        $processed = 0;
        $updated = 0;
        $failed = 0;

        $query->chunkById($chunk, function ($donations) use ($sync, $dryRun, &$processed, &$updated, &$failed): void {
            foreach ($donations as $donation) {
                $processed++;

                if ($dryRun) {
                    $this->line("[dry-run] Would backfill donation {$donation->public_id}");
                    $updated++;

                    continue;
                }

                try {
                    $sync->sync($donation);
                    $donation->refresh();

                    if ($donation->payment_method_exp_month && $donation->payment_method_exp_year) {
                        $this->info("Backfilled expiry for donation {$donation->public_id}");
                        $updated++;
                    } else {
                        $this->warn("No expiry returned for donation {$donation->public_id}");
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $this->error("Failed to backfill donation {$donation->public_id}: {$e->getMessage()}");
                    $failed++;
                }
            }
        });

        $this->info("Done. Processed: {$processed}, Updated: {$updated}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
