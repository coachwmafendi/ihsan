<?php

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Jobs\SendNewDonationNotification;
use App\Models\Donation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ResendNewDonationNotifications extends Command
{
    protected $signature = 'notifications:resend-new-donation
                            {--donation= : Resend for a specific donation by public_id}
                            {--days=7 : Resend for succeeded donations in the last N days}
                            {--all : Resend for all succeeded donations}
                            {--dry-run : Preview which notifications would be queued without sending}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Resend new donation notification emails to organisation admins';

    public function handle(): int
    {
        $donationOption = $this->option('donation');

        if ($donationOption !== null) {
            $donation = Donation::with(['donor', 'campaign.organization'])
                ->where('public_id', $donationOption)
                ->first();

            if ($donation === null) {
                $this->error("Donation [{$donationOption}] not found.");

                return self::FAILURE;
            }

            return $this->dispatchForDonations(collect([$donation]));
        }

        $query = Donation::with(['donor', 'campaign.organization'])
            ->where('status', DonationStatus::Succeeded)
            ->where('gross_amount', '>', 0);

        if (! $this->option('all')) {
            $days = (int) $this->option('days');
            $query->where('created_at', '>=', now()->subDays($days));
            $this->info("Targeting succeeded donations from the last {$days} day(s).");
        } else {
            $this->info('Targeting all succeeded donations.');
        }

        return $this->dispatchForDonations($query->get());
    }

    /**
     * @param  Collection<int, Donation>  $donations
     */
    private function dispatchForDonations($donations): int
    {
        if ($donations->isEmpty()) {
            $this->warn('No donations found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Public ID', 'Donor', 'Amount', 'Campaign', 'Date'],
            $donations->map(fn ($d) => [
                $d->public_id,
                $d->donor->name.' <'.$d->donor->email.'>',
                $d->formatted_amount,
                $d->campaign?->title,
                $d->created_at->format('d M Y'),
            ]),
        );

        $count = $donations->count();

        if ($this->option('dry-run')) {
            $this->info("Dry run — {$count} new donation notification(s) would be queued.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Queue {$count} new donation notification(s)?")) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($donations as $donation) {
            SendNewDonationNotification::dispatch($donation);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('New donation notifications queued.');

        return self::SUCCESS;
    }
}
