<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\RefundNotification;
use App\Models\Donation;
use App\Models\User;
use App\Support\Currency;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendRefundNotification implements ShouldQueue
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

        if (! ($settings['notify_refund'] ?? true)) {
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
                    new RefundNotification($donation, $amountDisplay)
                );
        }
    }

    private function formatAmount(Donation $donation): string
    {
        $symbol = Currency::symbol($donation->currency);
        $amount = number_format((float) $donation->gross_amount, 2);

        if (strtolower($donation->currency) !== 'myr' && $donation->base_amount !== null) {
            $base = number_format((float) $donation->base_amount, 2);

            return "≈ MYR {$base} ({$symbol} {$amount})";
        }

        return "{$symbol} {$amount}";
    }
}
