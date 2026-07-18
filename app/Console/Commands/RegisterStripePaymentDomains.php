<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Stripe\RegisterPaymentMethodDomains;
use App\Models\Organization;
use Illuminate\Console\Command;

class RegisterStripePaymentDomains extends Command
{
    protected $signature = 'stripe:register-payment-domains {--org= : Limit to a single organization id}';

    protected $description = 'Register connected-account Stripe payment method domains for wallet checkout (Apple Pay / Google Pay / Link)';

    public function handle(RegisterPaymentMethodDomains $action): int
    {
        $organizations = Organization::query()
            ->whereNotNull('stripe_account_id')
            ->when($this->option('org'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        $this->info("Registering domains for {$organizations->count()} organization(s)...\n");

        foreach ($organizations as $organization) {
            $registered = $action->register($organization);

            $this->line("Organization #{$organization->id}: {$organization->name}");

            if ($registered === []) {
                $this->warn('  No domains registered.');

                continue;
            }

            foreach ($registered as $domain) {
                $this->line("  ✓ {$domain}");
            }

            $this->newLine();
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
