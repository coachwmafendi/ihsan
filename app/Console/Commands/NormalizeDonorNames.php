<?php

namespace App\Console\Commands;

use App\Models\Donor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:normalize-donor-names {--dry-run : Show which donors would be cleaned without writing}')]
#[Description('Strip stray whitespace from donor names stored before it was squished on the way in')]
class NormalizeDonorNames extends Command
{
    /**
     * Donors saved before the model squished names carry the space their donor
     * typed - a trailing one on a first name reads as a gap in the middle of
     * the full name. The model now hides it when reading, but exports and
     * anything sent to Stripe still take the stored columns.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $cleaned = 0;

        Donor::query()->chunkById(200, function ($donors) use ($dryRun, &$cleaned): void {
            foreach ($donors as $donor) {
                $changes = $this->strayWhitespace($donor);

                if ($changes === []) {
                    continue;
                }

                $cleaned++;

                foreach ($changes as $field => [$before, $after]) {
                    $this->line(($dryRun ? '[DRY-RUN] ' : '').$donor->public_id." {$field}: \"{$before}\" → \"{$after}\"");
                }

                if (! $dryRun) {
                    // The saving hook squishes every name field, so saving an
                    // untouched model is enough to clean it.
                    $donor->save();
                }
            }
        });

        if ($cleaned === 0) {
            $this->components->info('No donor names need cleaning.');

            return self::SUCCESS;
        }

        $this->components->info(($dryRun ? 'Would clean ' : 'Cleaned ').$cleaned.' donor'.($cleaned === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function strayWhitespace(Donor $donor): array
    {
        $changes = [];

        foreach (['first_name', 'last_name', 'name'] as $field) {
            $value = $donor->getAttributes()[$field] ?? null;

            if (! is_string($value)) {
                continue;
            }

            $squished = Str::squish($value);

            if ($squished !== $value) {
                $changes[$field] = [$value, $squished];
            }
        }

        return $changes;
    }
}
