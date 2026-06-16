<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\NewSubscriptionNotification;
use App\Models\Donation;
use App\Models\User;
use App\Support\Currency;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewSubscriptionNotification implements ShouldQueue
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

        if (! ($settings['notify_new_subscription'] ?? true)) {
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
                    new NewSubscriptionNotification($donation, $amountDisplay)
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
