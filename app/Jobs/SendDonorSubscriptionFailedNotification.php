<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonorSubscriptionFailedNotification;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendDonorSubscriptionFailedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
    ) {}

    public function handle(): void
    {
        $subscription = Subscription::query()->whereKey($this->subscription->getKey())->first();

        if ($subscription === null) {
            return;
        }

        $subscription->loadMissing(['donor', 'campaign.organization', 'emailLogs']);

        $donor = $subscription->donor;

        if ($donor === null || ! filled($donor->email) || ! $donor->canReceiveEmails()) {
            return;
        }

        $org = $subscription->campaign?->organization;
        $settings = $org?->settings ?? [];

        if (! ($settings['notify_supporter_subscription_failed'] ?? true)) {
            return;
        }

        if ($this->notificationAlreadySent($subscription)) {
            return;
        }

        $messageId = Str::uuid()->toString();
        $mailable = new DonorSubscriptionFailedNotification($subscription, $messageId);

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $org,
            subscription: $subscription,
            messageId: $messageId,
        );

        Mail::to($donor->email)->queue($mailable);
    }

    private function notificationAlreadySent(Subscription $subscription): bool
    {
        return $subscription->emailLogs
            ->where('mailable_class', DonorSubscriptionFailedNotification::class)
            ->isNotEmpty();
    }
}
