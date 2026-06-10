<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Models\Subscription;
use App\Models\User;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionAmountChangedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public float $previousAmount,
    ) {}

    public function handle(): void
    {
        $subscription = Subscription::query()->whereKey($this->subscription->getKey())->first();

        if ($subscription === null) {
            return;
        }

        $subscription->loadMissing(['donor', 'campaign.organization']);

        $org = $subscription->campaign?->organization;

        if ($org === null) {
            return;
        }

        $amountDisplay = $subscription->currency_symbol.' '.number_format($subscription->amount, 2);

        // Notify donor
        if (filled($subscription->donor?->email)) {
            $donorMail = new SubscriptionAmountChangedNotification(
                $subscription,
                $this->previousAmount,
                $amountDisplay,
                true,
            );

            Mail::to($subscription->donor->email)
                ->queue($donorMail);
        }

        // Notify org admins
        $admins = User::query()
            ->where('organization_id', $org->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        foreach ($admins as $admin) {
            MailtrapThrottle::throttle();
            $adminMail = new SubscriptionAmountChangedNotification(
                $subscription,
                $this->previousAmount,
                $amountDisplay,
                false,
            );

            Mail::to($admin->email)
                ->queue($adminMail);
        }
    }
}
