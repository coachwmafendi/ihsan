<?php

namespace App\Console\Commands;

use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Models\Donation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;

#[Signature('app:resync-foreign-donation-exchange-rates {--dry-run : Show what would be updated without making changes}')]
#[Description('Resync exchange rates and base_amount for foreign-currency donations where base_amount is null')]
class ResyncForeignDonationExchangeRates extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $dryRun = $this->option('dry-run');

        $query = Donation::query()
            ->with('campaign.organization')
            ->whereRaw('LOWER(currency) != ?', ['myr'])
            ->whereNull('base_amount')
            ->whereNotNull('stripe_payment_intent_id');

        $count = $query->count();

        if ($count === 0) {
            $this->components->info('No foreign donations with missing base_amount found.');

            return self::SUCCESS;
        }

        $this->components->info("Found {$count} foreign donation(s) with missing base_amount.");

        if ($dryRun) {
            $query->chunkById(100, function ($donations) {
                foreach ($donations as $donation) {
                    $orgName = $donation->campaign?->organization?->name ?? 'No org';
                    $this->line("[DRY-RUN] Donation {$donation->public_id} — {$donation->currency} {$donation->gross_amount} (Org: {$orgName})");
                }
            });

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Starting...');
        $bar->start();

        $synced = 0;
        $failed = 0;

        $query->chunkById(50, function ($donations) use (&$synced, &$failed, $bar) {
            foreach ($donations as $donation) {
                $bar->setMessage("Donation {$donation->public_id}");

                try {
                    $organization = $donation->campaign?->organization;
                    $stripeOptions = filled($organization?->stripe_account_id)
                        ? ['stripe_account' => $organization->stripe_account_id]
                        : [];

                    app(SyncDonationStripeDetails::class)->sync($donation, null, $stripeOptions);

                    $synced++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error("Failed to resync donation {$donation->public_id}", [
                        'error' => $e->getMessage(),
                        'donation_id' => $donation->id,
                    ]);
                }

                $bar->advance();

                // Small delay to respect Stripe API rate limits
                usleep(100_000); // 100ms
            }
        });

        $bar->finish();
        $this->newLine();

        $this->components->info("Done. Synced: {$synced} | Failed: {$failed}");

        if ($failed > 0) {
            $this->components->warn("Check the logs for details on {$failed} failed donation(s).");
        }

        return self::SUCCESS;
    }
}
