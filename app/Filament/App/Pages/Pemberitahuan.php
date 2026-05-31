<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class Pemberitahuan extends Page
{
    protected string $view = 'filament.app.pages.pemberitahuan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Email Notifications';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'pemberitahuan';

    public bool $notifyNewDonation = false;

    public bool $dailyDonationSummary = false;

    public bool $failedPaymentNotification = false;

    public bool $notifyNewSubscription = false;

    public bool $notifySubscriptionCancelled = false;

    public bool $notifyLargeDonation = false;

    public int $largeDonationThreshold = 1000;

    public bool $notifyRefund = false;

    public bool $notifyCampaignMilestone = false;

    public bool $monthlyReport = false;

    public bool $weeklyReport = false;

    public function mount(): void
    {
        $settings = auth()->user()->organization?->settings ?? [];

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
        if (! str_starts_with($property, 'notify') && ! str_starts_with($property, 'daily') && ! str_starts_with($property, 'failed') && ! str_starts_with($property, 'large') && ! str_starts_with($property, 'monthly') && ! str_starts_with($property, 'weekly')) {
            return;
        }

        $this->save();
    }

    public function save(): void
    {
        $org = auth()->user()->organization;

        if ($org === null) {
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
    }
}
