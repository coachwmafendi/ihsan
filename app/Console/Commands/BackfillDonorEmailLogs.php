<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonationReceipt;
use App\Models\Donation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:backfill-donor-email-logs {--force : Skip confirmation prompt}')]
#[Description('Backfill donor_email_logs from historical donation receipts')]
class BackfillDonorEmailLogs extends Command
{
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will create donor_email_log records for donations that have receipt_sent_at. Continue?')) {
            return self::FAILURE;
        }

        $query = Donation::query()
            ->whereNotNull('receipt_sent_at')
            ->whereDoesntHave('emailLogs', fn ($q) => $q->where('mailable_class', DonationReceipt::class))
            ->with(['donor', 'campaign.organization'])
            ->orderBy('receipt_sent_at');

        $count = $query->count();

        if ($count === 0) {
            $this->components->info('No donations need backfilling.');

            return self::SUCCESS;
        }

        $this->components->info("Backfilling {$count} donation receipt email log entries.");

        $logger = app(LogDonorEmail::class);
        $bar = $this->output->createProgressBar($count);

        $query->chunkById(100, function ($donations) use ($logger, $bar) {
            foreach ($donations as $donation) {
                $donor = $donation->donor;
                $organization = $donation->campaign?->organization;

                if ($donor === null) {
                    $bar->advance();

                    continue;
                }

                $logger->handle(
                    donor: $donor,
                    mailable: new DonationReceipt($donation),
                    organization: $organization,
                    donation: $donation,
                );

                // Override the sent_at set by the logger to match the original receipt timestamp.
                $log = $donor->emailLogs()
                    ->where('donation_id', $donation->getKey())
                    ->where('mailable_class', DonationReceipt::class)
                    ->latest('created_at')
                    ->first();

                if ($log !== null) {
                    $log->update(['sent_at' => $donation->receipt_sent_at]);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->components->info('Backfill complete.');

        return self::SUCCESS;
    }
}
