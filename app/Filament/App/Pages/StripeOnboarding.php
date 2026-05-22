<?php

namespace App\Filament\App\Pages;

use App\Actions\Stripe\CreateConnectAccount;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Stripe\Exception\ApiErrorException;

class StripeOnboarding extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?string $title = 'Stripe Onboarding';

    protected string $view = 'filament.app.pages.stripe-onboarding';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?string $manual_account_id = null;

    public function mount(): void
    {
        $org = auth()->user()->organization;

        if ($org && $org->stripe_onboarded) {
            $this->redirect(route('filament.app.pages.insights'), navigate: true);
        }
    }

    public function getOnboardingUrl(): ?string
    {
        $org = auth()->user()->organization;

        if ($org === null || $org->stripe_account_id === null) {
            return null;
        }

        try {
            return app(CreateConnectAccount::class)->generateOnboardingLink($org);
        } catch (ApiErrorException) {
            return null;
        }
    }

    public function createStripeAccount(): void
    {
        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        try {
            app(CreateConnectAccount::class)->create($org);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal buat akaun Stripe')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Akaun Stripe berjaya dibuat')
            ->body('Sila sambung ke Stripe untuk melengkapkan KYC.')
            ->success()
            ->send();
    }

    public function connectManualAccount(): void
    {
        $this->validate([
            'manual_account_id' => ['required', 'string', 'max:500'],
        ]);

        $input = trim($this->manual_account_id);

        $accountId = null;

        if (str_starts_with($input, 'acct_')) {
            $accountId = $input;
        } elseif (preg_match('#/acct_([a-zA-Z0-9_]+)#', $input, $matches)) {
            $accountId = 'acct_'.$matches[1];
        }

        if ($accountId === null) {
            $this->addError('manual_account_id', 'Masukkan ID akaun Stripe yang sah (acct_xxx) atau pautan dashboard Stripe.');

            return;
        }

        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        $org->update(['stripe_account_id' => $accountId]);

        Notification::make()
            ->title('Akaun Stripe disambung')
            ->success()
            ->send();
    }
}
