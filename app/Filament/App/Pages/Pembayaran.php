<?php

namespace App\Filament\App\Pages;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

class Pembayaran extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament.app.pages.pembayaran';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Stripe';

    protected static ?string $title = 'Stripe';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'stripe-conn';

    public array $currencies = ['myr' => true, 'usd' => false, 'sgd' => false];

    public string $feeCollectionMethod = 'invoice';

    public bool $showFeeConfirm = false;

    public function mount(): void
    {
        $org = auth()->user()->organization;
        $settings = $org?->settings ?? [];
        $accepted = $settings['accepted_currencies'] ?? ['myr'];

        $this->currencies = [
            'myr' => true,
            'usd' => in_array('usd', $accepted),
            'sgd' => in_array('sgd', $accepted),
        ];

        $this->feeCollectionMethod = $org?->fee_collection_method ?? 'invoice';
    }

    public function updatedCurrencies(): void
    {
        $org = auth()->user()->organization;
        if ($org === null) {
            return;
        }

        $accepted = ['myr'];
        if ($this->currencies['usd']) {
            $accepted[] = 'usd';
        }
        if ($this->currencies['sgd']) {
            $accepted[] = 'sgd';
        }

        $settings = array_merge($org->settings ?? [], ['accepted_currencies' => $accepted]);
        $org->update(['settings' => $settings]);

        Notification::make()
            ->title('Accepted currencies updated')
            ->success()
            ->send();
    }

    public function saveFeeCollection(): void
    {
        $org = auth()->user()->organization;
        if ($org === null) {
            return;
        }

        $org->update(['fee_collection_method' => $this->feeCollectionMethod]);

        $this->showFeeConfirm = false;

        Notification::make()
            ->title('Fee collection method updated')
            ->success()
            ->send();
    }

    public function getProcessingFeePercent(): string
    {
        return number_format((float) config('services.stripe.processing_fee_percent', 2.5), 1);
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
            ->label('Reconnect')
            ->icon('heroicon-o-arrow-path')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reconnect Stripe Connect?')
            ->modalDescription('This will disconnect your current Stripe Connect account. You will need to reconnect a Stripe account to continue using the panel.')
            ->modalSubmitActionLabel('Yes, reconnect')
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
                    ->title('Stripe connection reset')
                    ->warning()
                    ->send();

                $this->redirect(route('filament.app.pages.stripe-onboarding'), navigate: true);
            });
    }
}
