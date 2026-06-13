<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Notifications extends Component
{
    public bool $notifyNewDonation = true;

    public bool $dailyDonationSummary = false;

    public bool $failedPaymentNotification = true;

    public bool $notifyNewSubscription = true;

    public bool $notifySubscriptionCancelled = true;

    public bool $notifyLargeDonation = false;

    public int $largeDonationThreshold = 1000;

    public bool $notifyRefund = true;

    public bool $notifyCampaignMilestone = false;

    public bool $monthlyReport = false;

    public bool $weeklyReport = false;

    public function mount(): void
    {
        $settings = Auth::user()?->organization?->settings ?? [];

        $this->notifyNewDonation = (bool) ($settings['notify_new_donation'] ?? true);
        $this->dailyDonationSummary = (bool) ($settings['daily_donation_summary'] ?? false);
        $this->failedPaymentNotification = (bool) ($settings['failed_payment_notification'] ?? true);
        $this->notifyNewSubscription = (bool) ($settings['notify_new_subscription'] ?? true);
        $this->notifySubscriptionCancelled = (bool) ($settings['notify_subscription_cancelled'] ?? true);
        $this->notifyLargeDonation = (bool) ($settings['notify_large_donation'] ?? false);
        $this->largeDonationThreshold = (int) ($settings['large_donation_threshold'] ?? 1000);
        $this->notifyRefund = (bool) ($settings['notify_refund'] ?? true);
        $this->notifyCampaignMilestone = (bool) ($settings['notify_campaign_milestone'] ?? false);
        $this->monthlyReport = (bool) ($settings['monthly_report'] ?? false);
        $this->weeklyReport = (bool) ($settings['weekly_report'] ?? false);
    }

    public function updated(string $property): void
    {
        if (! str_starts_with($property, 'notify')
            && ! str_starts_with($property, 'daily')
            && ! str_starts_with($property, 'failed')
            && ! str_starts_with($property, 'large')
            && ! str_starts_with($property, 'monthly')
            && ! str_starts_with($property, 'weekly')
        ) {
            return;
        }

        $this->save();
    }

    public function save(): void
    {
        $org = Auth::user()?->organization;
        if (! $org) {
            return;
        }

        $settings = array_merge($org->settings ?? [], [
            'notify_new_donation' => $this->notifyNewDonation,
            'daily_donation_summary' => $this->dailyDonationSummary,
            'failed_payment_notification' => $this->failedPaymentNotification,
            'notify_new_subscription' => $this->notifyNewSubscription,
            'notify_subscription_cancelled' => $this->notifySubscriptionCancelled,
            'notify_large_donation' => $this->notifyLargeDonation,
            'large_donation_threshold' => $this->largeDonationThreshold,
            'notify_refund' => $this->notifyRefund,
            'notify_campaign_milestone' => $this->notifyCampaignMilestone,
            'monthly_report' => $this->monthlyReport,
            'weekly_report' => $this->weeklyReport,
        ]);

        $org->update(['settings' => $settings]);

        $this->dispatch('notify', message: 'Notification preferences saved.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.app.settings.notifications', ['title' => 'Settings — Notifications']);
    }
}
