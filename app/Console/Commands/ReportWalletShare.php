<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WalletShareReport;
use Illuminate\Console\Command;

/**
 * The same figures the Platform Overview carries, for anyone already at a
 * shell. The arithmetic lives in WalletShareReport so the page and the command
 * can never disagree about what the share is.
 */
class ReportWalletShare extends Command
{
    protected $signature = 'ihsan:wallet-share {--weeks=8 : How many weeks back to report}';

    protected $description = 'Report the share of donations completed by wallet, week by week';

    public function handle(WalletShareReport $report): int
    {
        $weeks = $report->weekly((int) $this->option('weeks'));

        if ($weeks === []) {
            $this->info('No successful donations in that window.');

            return self::SUCCESS;
        }

        $this->table(
            ['Week of', 'Donations', 'Wallet', 'Card', 'Wallet share', 'Median gift'],
            array_map(fn (array $week): array => [
                $week['week'].($week['thin'] ? ' *' : ''),
                (string) $week['donations'],
                (string) $week['wallet'],
                (string) $week['card'],
                $week['share'] === null ? '—' : number_format($week['share'], 1).'%',
                'MYR '.number_format($week['median'], 2),
            ], $weeks),
        );

        $this->line('');
        $this->line('A week under '.WalletShareReport::MinimumWeeklyDonations.' donations is marked - one donor moves the share too far to read.');

        return self::SUCCESS;
    }
}
