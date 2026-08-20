<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonorPaymentMethodChangedNotification;
use App\Models\DonorEmailLog;
use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendDonorPaymentMethodChangedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public ?DonorPaymentMethod $previousPaymentMethod,
        public DonorPaymentMethod $newPaymentMethod,
    ) {}

    public function handle(): void
    {
        $subscription = Subscription::query()->whereKey($this->subscription->getKey())->first();

        if ($subscription === null) {
            return;
        }

        $subscription->loadMissing(['donor', 'campaign.organization', 'donorPaymentMethod', 'emailLogs']);

        $donor = $subscription->donor;

        if ($donor === null || ! filled($donor->email) || ! $donor->canReceiveEmails()) {
            return;
        }

        if ($this->notificationAlreadySent($subscription)) {
            return;
        }

        $org = $subscription->campaign?->organization;
        $messageId = Str::uuid()->toString();
        $mailable = new DonorPaymentMethodChangedNotification(
            $subscription,
            $this->previousPaymentMethod,
            $this->newPaymentMethod,
            $messageId
        );

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $org,
            subscription: $subscription,
            messageId: $messageId,
            metadata: [
                'previous_payment_method_id' => $this->previousPaymentMethod?->getKey(),
                'new_payment_method_id' => $this->newPaymentMethod->getKey(),
            ],
        );

        Mail::to($donor->email)->queue($mailable);
    }

    private function notificationAlreadySent(Subscription $subscription): bool
    {
        return $subscription->emailLogs
            ->where('mailable_class', DonorPaymentMethodChangedNotification::class)
            ->contains(function (DonorEmailLog $log) {
                $metadata = $log->metadata ?? [];

                return ($metadata['new_payment_method_id'] ?? null) === $this->newPaymentMethod->getKey();
            });
    }
}
