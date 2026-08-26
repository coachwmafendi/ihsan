<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('app:prune-empty-organizations {--dry-run} {--keep-days=30}')]
#[Description('Permanently delete soft-deleted organizations that never held any data')]
class PruneEmptyOrganizations extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $keepDays = (int) $this->option('keep-days');

        $query = $this->prunableQuery($keepDays);

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No empty organizations to prune.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info(sprintf('Would delete %s empty organization%s.', number_format($total), $total === 1 ? '' : 's'));

            return self::SUCCESS;
        }

        $deleted = 0;

        (clone $query)->each(function (Organization $organization) use (&$deleted): void {
            $organization->forceDelete();
            $deleted++;
        });

        $this->info(sprintf('Deleted %s empty organization%s.', number_format($deleted), $deleted === 1 ? '' : 's'));

        return self::SUCCESS;
    }

    /**
     * Soft-deleted organizations that are safe to remove for good: trashed long
     * enough ago, never connected to Stripe, and with no campaigns or processing
     * fees. Anything with financial history is deliberately left untouched.
     */
    private function prunableQuery(int $keepDays): Builder
    {
        return Organization::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($keepDays))
            ->whereNull('stripe_account_id')
            ->whereDoesntHave('campaigns')
            ->whereDoesntHave('processingFees');
    }
}
