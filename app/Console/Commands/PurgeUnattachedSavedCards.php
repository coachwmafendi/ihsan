<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use Illuminate\Console\Command;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class PurgeUnattachedSavedCards extends Command
{
    protected $signature = 'donors:purge-unattached-cards {--dry-run : List cards without deleting}';

    protected $description = 'Remove saved cards that are not attached to their customer on Stripe (phantom cards that cannot be charged)';

    public function handle(): int
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $cards = DonorPaymentMethod::with('donor')->get();
        $this->info("Checking {$cards->count()} saved card(s)...\n");

        $purged = 0;

        foreach ($cards as $card) {
            $donor = $card->donor;

            if (! $donor) {
                continue;
            }

            if ($this->isAttached($card->stripe_payment_method_id, $this->resolveOrganization($donor))) {
                continue;
            }

            $this->line("Unattached: {$card->stripe_payment_method_id} (donor #{$donor->id} {$donor->email})");

            if (! $this->option('dry-run')) {
                $card->delete();
            }

            $purged++;
        }

        $verb = $this->option('dry-run') ? 'Would purge' : 'Purged';
        $this->info("\n{$verb} {$purged} unattached card(s).");

        return self::SUCCESS;
    }

    private function isAttached(string $paymentMethodId, ?Organization $organization): bool
    {
        $stripeOptions = $organization?->stripe_account_id
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);

            return filled($paymentMethod->customer);
        } catch (\Throwable) {
            // Missing or detached payment method.
            return false;
        }
    }

    private function resolveOrganization(Donor $donor): ?Organization
    {
        return $donor->donations()
            ->with('campaign.organization')
            ->latest()
            ->get()
            ->map(fn ($donation) => $donation->campaign?->organization)
            ->first(fn (?Organization $organization) => $organization?->stripe_account_id !== null);
    }
}
