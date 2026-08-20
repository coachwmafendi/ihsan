<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonorPaymentMethodExpiringNotification;
use App\Models\DonorEmailLog;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendDonorPaymentMethodExpiringNotification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int>|Collection<int, Subscription>  $subscriptions
     */
    public function __construct(
        public array|Collection $subscriptions,
        public int $daysRemaining,
    ) {}

    public function handle(): void
    {
        $subscriptionIds = $this->subscriptions instanceof Collection
            ? $this->subscriptions->modelKeys()
            : $this->subscriptions;

        if (empty($subscriptionIds)) {
            return;
        }

        $subscriptions = Subscription::query()
            ->whereKey($subscriptionIds)
            ->with(['donor', 'campaign.organization', 'donorPaymentMethod', 'emailLogs'])
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $primarySubscription = $subscriptions->first();
        $donor = $primarySubscription->donor;

        if ($donor === null || ! filled($donor->email) || ! $donor->canReceiveEmails()) {
            return;
        }

        if ($this->notificationAlreadySent($subscriptions)) {
            return;
        }

        $organization = $primarySubscription->campaign?->organization;
        $messageId = Str::uuid()->toString();
        $mailable = new DonorPaymentMethodExpiringNotification($subscriptions, $this->daysRemaining, $messageId);

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $organization,
            subscription: $primarySubscription,
            messageId: $messageId,
            metadata: [
                'days_remaining' => $this->daysRemaining,
                'subscription_ids' => $subscriptions->modelKeys(),
            ],
        );

        Mail::to($donor->email)->queue($mailable);
    }

    /**
     * @param  Collection<int, Subscription>  $subscriptions
     */
    private function notificationAlreadySent(Collection $subscriptions): bool
    {
        $primarySubscription = $subscriptions->first();

        return $primarySubscription->emailLogs
            ->where('mailable_class', DonorPaymentMethodExpiringNotification::class)
            ->contains(function (DonorEmailLog $log) {
                $metadata = $log->metadata ?? [];

                return ($metadata['days_remaining'] ?? null) === $this->daysRemaining;
            });
    }
}
