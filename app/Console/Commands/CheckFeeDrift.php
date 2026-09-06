<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Mail\FeeDriftAlert;
use App\Models\Donation;
use App\Services\DonationFeeEstimator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Compare what Stripe actually charged against the rate the fee estimator
 * quotes today.
 *
 * The foreign-currency rates were wrong by a percentage point for months and
 * nothing noticed, because nothing ever checked the quote against a settled
 * transaction. Donors covered too little and organizations quietly received
 * less than the donation intended. This reads the fee back off recent
 * donations and reports when the two drift apart.
 */
class CheckFeeDrift extends Command
{
    protected $signature = 'ihsan:check-fee-drift {--days=7 : How far back to look} {--threshold=0.5 : Percentage points of drift worth reporting}';

    protected $description = 'Report when the processor fee we quote drifts from what Stripe charged';

    /**
     * Below this many donations a bucket is noise, not a trend: one unusual
     * card should never raise an alarm.
     */
    private const MinimumSampleSize = 3;

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = (float) $this->option('threshold');

        $donations = Donation::query()
            ->where('status', DonationStatus::Succeeded)
            ->whereNotNull('stripe_charge_id')
            ->where('stripe_fee', '>', 0)
            ->where('created_at', '>=', now()->subDays($days))
            ->get(['currency', 'gross_amount', 'donor_fee_covered', 'stripe_fee', 'exchange_rate', 'donor_country']);

        if ($donations->isEmpty()) {
            $this->info("No settled donations in the last {$days} day(s); nothing to measure.");

            return self::SUCCESS;
        }

        $buckets = [];

        foreach ($donations as $donation) {
            $measured = $this->measuredPercent($donation);

            if ($measured === null) {
                continue;
            }

            $quoted = DonationFeeEstimator::percentRate(
                $donation->currency,
                'stripe',
                $donation->donor_country,
            ) * 100;

            $key = strtoupper($donation->currency).' / '.strtoupper((string) ($donation->donor_country ?: 'unknown'));
            $buckets[$key][] = $measured - $quoted;
        }

        $drifting = [];

        foreach ($buckets as $key => $differences) {
            if (count($differences) < self::MinimumSampleSize) {
                continue;
            }

            $average = array_sum($differences) / count($differences);

            $this->line(sprintf('%-22s n=%-4d drift %+.2f points', $key, count($differences), $average));

            if (abs($average) >= $threshold) {
                $drifting[$key] = ['count' => count($differences), 'drift' => round($average, 2)];
            }
        }

        if ($drifting === []) {
            $this->info('Quoted rates match what Stripe charged.');

            return self::SUCCESS;
        }

        $this->warn('Fee drift detected in '.count($drifting).' group(s).');

        $adminEmail = config('app.admin_email');

        if (blank($adminEmail)) {
            Log::warning('Fee drift detected but app.admin_email is not set.', ['groups' => $drifting]);

            return self::SUCCESS;
        }

        Mail::to($adminEmail)->queue(new FeeDriftAlert($drifting, $days, $threshold));

        return self::SUCCESS;
    }

    /**
     * The percentage Stripe actually took, net of its fixed RM1.00.
     *
     * Fees settle in ringgit while the donation may be presented in another
     * currency, so the charge is converted before the two are compared.
     */
    private function measuredPercent(Donation $donation): ?float
    {
        $exchangeRate = (float) ($donation->exchange_rate ?: 1);
        $charged = ((float) $donation->gross_amount + (float) $donation->donor_fee_covered) * $exchangeRate;

        if ($charged <= 0) {
            return null;
        }

        return (((float) $donation->stripe_fee - 1.00) / $charged) * 100;
    }
}
