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

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'stripe-conn';

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
            ->modalHeading('Sambung Semula Stripe Connect?')
            ->modalDescription('Tindakan ini akan memutuskan sambungan Stripe Connect semasa. Anda perlu menyambung semula akaun Stripe untuk terus menggunakan panel.')
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
