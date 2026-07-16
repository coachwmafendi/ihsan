<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\NewSubscriptionNotification;
use App\Models\Donation;
use App\Models\User;
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

        $amountDisplay = $donation->display_payment_amount;

        $delay = MailtrapThrottle::delaySeconds();

        foreach ($admins as $index => $admin) {
            Mail::to($admin->email)
                ->later(
                    now()->addSeconds($index * $delay),
                    new NewSubscriptionNotification($donation, $amountDisplay)
                );
        }
    }
}
