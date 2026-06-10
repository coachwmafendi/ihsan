<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\SubscriptionCancelledNotification;
use App\Models\Subscription;
use App\Models\User;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionCancelledNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
    ) {}

    public function handle(): void
    {
        $this->subscription->loadMissing(['donor', 'campaign.organization']);

        $org = $this->subscription->campaign?->organization;

        if ($org === null) {
            return;
        }

        $settings = $org->settings ?? [];

        if (! ($settings['notify_subscription_cancelled'] ?? true)) {
            return;
        }

        $admins = User::query()
            ->where('organization_id', $org->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        foreach ($admins as $admin) {
            MailtrapThrottle::throttle();
            Mail::to($admin->email)
                ->queue(new SubscriptionCancelledNotification($this->subscription));
        }
    }
}
