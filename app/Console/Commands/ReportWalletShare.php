<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * How many donations are finished with a wallet rather than a card.
 *
 * The monthly offer now stands between choosing an amount and reaching the
 * wallet button. That may raise the average gift, and it may cost some of the
 * fastest-converting donors we have - a wallet payment is two taps and no
 * form. Both are plausible and neither is knowable by argument, so this counts
 * them week by week and lets the numbers say which happened.
 */
class ReportWalletShare extends Command
{
    protected $signature = 'ihsan:wallet-share {--weeks=8 : How many weeks back to report}';

    protected $description = 'Report the share of donations completed by wallet, week by week';

    /**
     * Below this a week is noise: one donor swings the percentage by tens of
     * points and the trend line means nothing.
     */
    private const MinimumWeeklyDonations = 5;

    public function handle(): int
    {
        $weeks = max(1, (int) $this->option('weeks'));
        $since = CarbonImmutable::now()->startOfWeek()->subWeeks($weeks - 1);

        $donations = Donation::query()
            ->where('status', DonationStatus::Succeeded)
            ->where('created_at', '>=', $since)
            ->get(['created_at', 'payment_method_type', 'type', 'gross_amount', 'base_amount', 'currency']);

        if ($donations->isEmpty()) {
            $this->info('No successful donations in that window.');

            return self::SUCCESS;
        }

        $rows = $donations
            ->groupBy(fn (Donation $donation) => CarbonImmutable::parse($donation->created_at)->startOfWeek()->format('Y-m-d'))
            ->sortKeys()
            ->map(fn (Collection $week, string $starting) => $this->summarise($week, $starting))
            ->values()
            ->all();

        $this->table(
            ['Week of', 'Donations', 'Wallet', 'Card', 'Wallet share', 'Median gift'],
            $rows,
        );

        $this->line('');
        $this->line('A week under '.self::MinimumWeeklyDonations.' donations is marked - one donor moves the share too far to read.');

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Donation>  $week
     * @return array<int, string>
     */
    private function summarise(Collection $week, string $starting): array
    {
        $wallet = $week->filter(fn (Donation $d) => in_array($d->payment_method_type, ['apple_pay', 'google_pay'], true))->count();
        $total = $week->count();
        $card = $total - $wallet;

        // In ringgit, so a week of dollars does not read as a week of pennies.
        $amounts = $week
            ->map(fn (Donation $d) => (float) ($d->base_amount ?? $d->gross_amount))
            ->sort()
            ->values();

        $median = $amounts->isEmpty() ? 0.0 : (float) $amounts[(int) floor(($amounts->count() - 1) / 2)];

        return [
            $starting.($total < self::MinimumWeeklyDonations ? ' *' : ''),
            (string) $total,
            (string) $wallet,
            (string) $card,
            $total === 0 ? '—' : number_format($wallet / $total * 100, 1).'%',
            'MYR '.number_format($median, 2),
        ];
    }
}
