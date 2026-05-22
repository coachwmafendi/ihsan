<?php

namespace App\Filament\App\Pages;

use App\Actions\Stripe\CreateConnectAccount;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StripeOnboarding extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?string $title = 'Stripe Onboarding';

    protected string $view = 'filament.app.pages.stripe-onboarding';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getLayout(): string
    {
        return 'filament-panels::components.layout.simple';
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
        } catch (\Throwable) {
            return null;
        }
    }

    public function createExpressAccount(): void
    {
        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        try {
            $account = app(CreateConnectAccount::class)->create($org);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal mencipta akaun Stripe')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        try {
            $url = app(CreateConnectAccount::class)->generateOnboardingLink($org);

            $this->redirect($url);
        } catch (\Throwable) {
            Notification::make()
                ->title('Akaun Stripe dicipta')
                ->body('Sila lengkapkan onboarding di Stripe untuk menggunakan panel.')
                ->warning()
                ->send();
        }
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
            $this->addError('manual_account_id', 'Masukkan ID akaun Stripe yang sah (acct_xxx)');

            return;
        }

        $result = app(CreateConnectAccount::class)->verifyAndConnect($accountId);

        if (! $result['valid']) {
            $this->addError('manual_account_id', $result['reason']);

            return;
        }

        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        $org->update([
            'stripe_account_id' => $accountId,
            'stripe_onboarded' => $result['charges_enabled'],
        ]);

        if ($result['charges_enabled']) {
            Notification::make()
                ->title('Akaun Stripe berjaya disambung')
                ->success()
                ->send();

            $this->redirect(route('filament.app.pages.insights'), navigate: true);
        } else {
            Notification::make()
                ->title('Akaun Stripe dijumpai')
                ->body('Sila selesaikan KYC di dashboard Stripe sebelum menggunakan panel.')
                ->warning()
                ->send();
        }
    }
}
