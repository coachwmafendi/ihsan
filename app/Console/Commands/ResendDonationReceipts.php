<?php

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Jobs\SendDonationReceipt;
use App\Models\Donation;
use Illuminate\Console\Command;

class ResendDonationReceipts extends Command
{
    protected $signature = 'donations:resend-receipts
                            {--days=7 : Resend receipts for donations in the last N days}
                            {--all : Resend receipts for all succeeded donations}
                            {--dry-run : Preview which donations would be resent without sending}';

    protected $description = 'Resend donation receipt emails to donors';

    public function handle(): int
    {
        $query = Donation::with(['donor', 'campaign.organization'])
            ->where('status', DonationStatus::Succeeded)
            ->where('gross_amount', '>', 0);

        if (! $this->option('all')) {
            $days = (int) $this->option('days');
            $query->where('created_at', '>=', now()->subDays($days));
            $this->info("Targeting donations from the last {$days} day(s).");
        } else {
            $this->info('Targeting all succeeded donations.');
        }

        $donations = $query->get();

        if ($donations->isEmpty()) {
            $this->warn('No donations found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Donor', 'Amount', 'Date'],
            $donations->map(fn ($d) => [
                $d->public_id,
                $d->donor->name.' <'.$d->donor->email.'>',
                $d->formatted_amount,
                $d->created_at->format('d M Y'),
            ]),
        );

        if ($this->option('dry-run')) {
            $this->info("Dry run — {$donations->count()} receipt(s) would be sent.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Send {$donations->count()} receipt(s)?")) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($donations->count());
        $bar->start();

        foreach ($donations as $donation) {
            SendDonationReceipt::dispatch($donation);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Receipts queued.');

        return self::SUCCESS;
    }
}
