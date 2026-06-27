<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonorSubscriptionCancelledNotification;
use App\Models\DonorEmailLog;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendDonorSubscriptionCancelledNotification implements ShouldQueue
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

        $subscription->loadMissing(['donor', 'campaign.organization', 'donations']);

        $donor = $subscription->donor;

        if ($donor === null || ! filled($donor->email) || ! $donor->canReceiveEmails()) {
            return;
        }

        if (DonorEmailLog::query()
            ->where('subscription_id', $subscription->getKey())
            ->where('mailable_class', DonorSubscriptionCancelledNotification::class)
            ->exists()) {
            return;
        }

        $org = $subscription->campaign?->organization;
        $messageId = Str::uuid()->toString();
        $mailable = new DonorSubscriptionCancelledNotification($subscription, $messageId);

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $org,
            subscription: $subscription,
            messageId: $messageId,
        );

        Mail::to($donor->email)->queue($mailable);
    }
}
