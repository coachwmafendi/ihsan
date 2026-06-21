<?php

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Enums\UserRole;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Mail\SupporterSubscriptionAmountChangedNotification;
use App\Models\Subscription;
use App\Models\User;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

        // Notify supporter
        if (filled($subscription->donor?->email) && $subscription->donor->canReceiveEmails()) {
            $messageId = Str::uuid()->toString();
            $donorMail = new SupporterSubscriptionAmountChangedNotification(
                $subscription,
                $this->previousAmount,
                $messageId,
            );

            app(LogDonorEmail::class)->handle(
                donor: $subscription->donor,
                mailable: $donorMail,
                organization: $org,
                subscription: $subscription,
                metadata: [
                    'previous_amount' => $this->previousAmount,
                    'amount_display' => $amountDisplay,
                ],
                messageId: $messageId,
            );

            Mail::to($subscription->donor->email)
                ->queue($donorMail);
        }

        // Notify org admins
        $admins = User::query()
            ->where('organization_id', $org->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        $delay = MailtrapThrottle::delaySeconds();

        foreach ($admins as $index => $admin) {
            $adminMail = new SubscriptionAmountChangedNotification(
                $subscription,
                $this->previousAmount,
                $amountDisplay,
                false,
                null,
                $admin,
            );

            Mail::to($admin->email)
                ->later(now()->addSeconds($index * $delay), $adminMail);
        }
    }
}
