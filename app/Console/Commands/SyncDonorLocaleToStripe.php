<?php

namespace App\Console\Commands;

use App\Models\Donor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Stripe\Customer;
use Stripe\Exception\InvalidRequestException;
use Stripe\Stripe;

#[Signature('app:sync-donor-locale-to-stripe {--default= : Default locale for donors without a locale} {--stripe-account= : Connected Stripe account ID}')]
#[Description('Sync donor locale to existing Stripe Customer records')]
class SyncDonorLocaleToStripe extends Command
{
    public function handle(): int
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $defaultLocale = $this->option('default');
        $stripeAccount = $this->option('stripe-account');
        $stripeOptions = filled($stripeAccount) ? ['stripe_account' => $stripeAccount] : [];

        $query = Donor::query()->whereNotNull('stripe_customer_id');

        if ($defaultLocale === null) {
            $query->whereNotNull('locale');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('No donors with stripe_customer_id found.');

            return self::SUCCESS;
        }

        $this->info("Syncing locales for {$total} donor(s)...");

        $success = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($query->cursor() as $donor) {
            $locale = $donor->locale ?? $defaultLocale;

            if (blank($locale)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            try {
                Customer::update($donor->stripe_customer_id, [
                    'preferred_locales' => [$locale],
                ], $stripeOptions);

                $success++;
            } catch (InvalidRequestException $e) {
                if (str_contains($e->getMessage(), 'No such customer')) {
                    $skipped++;
                } else {
                    report($e);
                    $failed++;
                }
            } catch (\Exception $e) {
                report($e);
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Success: {$success}, Skipped: {$skipped}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
