<?php

namespace App\Filament\App\Pages;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

class Settings extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament.app.pages.settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 999;

    public bool $notifyNewDonation = false;

    public bool $dailyDonationSummary = false;

    public bool $failedPaymentNotification = false;

    public function mount(): void
    {
        $settings = auth()->user()->organization?->settings ?? [];

        $this->notifyNewDonation = (bool) ($settings['notify_new_donation'] ?? true);
        $this->dailyDonationSummary = (bool) ($settings['daily_donation_summary'] ?? false);
        $this->failedPaymentNotification = (bool) ($settings['failed_payment_notification'] ?? true);
    }

    public function saveNotificationSettings(): void
    {
        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        $settings = array_merge($org->settings ?? [], [
            'notify_new_donation' => $this->notifyNewDonation,
            'daily_donation_summary' => $this->dailyDonationSummary,
            'failed_payment_notification' => $this->failedPaymentNotification,
        ]);

        $org->update(['settings' => $settings]);

        Notification::make()
            ->title('Notification preferences saved.')
            ->success()
            ->send();
    }

    public function stripeAccount(): ?StripeAccount
    {
        $org = auth()->user()->organization;

        if ($org === null || $org->stripe_account_id === null) {
            return null;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        return StripeAccount::retrieve($org->stripe_account_id);
    }

    public function reconnectAction(): Action
    {
        return Action::make('reconnect')
            ->label('Sambung Semula')
            ->icon('heroicon-o-arrow-path')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Sambung Semula Stripe?')
            ->modalDescription('Tindakan ini akan memutuskan sambungan Stripe semasa. Anda perlu menyambung semula akaun Stripe untuk terus menggunakan panel.')
            ->modalSubmitActionLabel('Ya, sambung semula')
            ->action(function () {
                $org = auth()->user()->organization;

                if ($org === null) {
                    return;
                }

                $org->update([
                    'stripe_account_id' => null,
                    'stripe_onboarded' => false,
                ]);

                Notification::make()
                    ->title('Sambungan Stripe telah direset')
                    ->warning()
                    ->send();

                $this->redirect(route('filament.app.pages.stripe-onboarding'), navigate: true);
            });
    }
}
