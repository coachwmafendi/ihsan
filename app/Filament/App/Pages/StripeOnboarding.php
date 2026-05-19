<?php

namespace App\Filament\App\Pages;

use App\Actions\Stripe\CreateConnectAccount;
use BackedEnum;
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

    public function getOnboardingUrl(): ?string
    {
        $org = auth()->user()->organization;

        if ($org === null || $org->stripe_account_id === null) {
            return null;
        }

        return app(CreateConnectAccount::class)->generateOnboardingLink($org);
    }
}
