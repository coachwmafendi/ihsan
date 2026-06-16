<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\NewDonationNotification;
use App\Models\Donation;
use App\Models\User;
use App\Support\Currency;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewDonationNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation,
    ) {}

    public function handle(): void
    {
        $donation = Donation::query()->whereKey($this->donation->getKey())->first();

        if ($donation === null) {
            return;
        }

        $donation->loadMissing(['donor', 'campaign.organization']);

        $org = $donation->campaign?->organization;

        if ($org === null) {
            return;
        }

        $settings = $org->settings ?? [];

        if (! ($settings['notify_new_donation'] ?? true)) {
            return;
        }

        $largeDonationEnabled = $settings['notify_large_donation'] ?? false;
        $largeThreshold = (int) ($settings['large_donation_threshold'] ?? 1000);

        $amount = (float) ($donation->base_amount ?? $donation->gross_amount);

        if ($largeDonationEnabled && $amount >= $largeThreshold) {
            return;
        }

        $admins = User::query()
            ->where('organization_id', $org->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        $amountDisplay = $this->formatAmount($donation);

        $delay = MailtrapThrottle::delaySeconds();

        foreach ($admins as $index => $admin) {
            Mail::to($admin->email)
                ->later(
                    now()->addSeconds($index * $delay),
                    new NewDonationNotification($donation, $amountDisplay)
                );
        }
    }

    private function formatAmount(Donation $donation): string
    {
        $symbol = Currency::symbol($donation->currency);
        $total = (float) $donation->gross_amount + (float) $donation->donor_fee_covered;
        $amount = number_format($total, 2);

        if (strtolower($donation->currency) !== 'myr' && $donation->base_amount !== null) {
            $exchangeRate = (float) ($donation->exchange_rate ?? 0);
            $feeInBase = $exchangeRate > 0 ? round((float) $donation->donor_fee_covered * $exchangeRate, 2) : (float) $donation->donor_fee_covered;
            $base = number_format((float) $donation->base_amount + $feeInBase, 2);

            return "{$symbol} {$amount} (≈ MYR {$base})";
        }

        return "{$symbol} {$amount}";
    }
}
