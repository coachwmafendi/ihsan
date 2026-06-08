<?php

namespace App\Filament\Pages;

use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

class StripeSettings extends Page
{
    protected string $view = 'filament.admin.pages.stripe-settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Stripe';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'processing_fee_percent' => Setting::get('payment_processing_fee_percent', config('services.stripe.processing_fee_percent', 2.5)),
        ]);
    }

    protected function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('processing_fee_percent')
                    ->label('Processing Fee (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.1)
                    ->suffix('%')
                    ->required(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('payment_processing_fee_percent', (float) $data['processing_fee_percent']);

        config(['services.stripe.processing_fee_percent' => (float) $data['processing_fee_percent']]);

        Notification::make()
            ->title('Stripe settings saved.')
            ->success()
            ->send();
    }

    public function getApiMode(): string
    {
        $key = config('services.stripe.secret');

        if (str_starts_with((string) $key, 'sk_live_')) {
            return 'Live';
        }

        if (str_starts_with((string) $key, 'sk_test_')) {
            return 'Test';
        }

        return 'Not configured';
    }

    public function getPlatformAccount(): ?array
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            return null;
        }

        try {
            Stripe::setApiKey($secret);
            $account = StripeAccount::retrieve();

            return [
                'id' => $account->id,
                'business_name' => $account->business_profile->name ?? $account->settings->dashboard->display_name ?? null,
                'email' => $account->email ?? null,
                'country' => $account->country ?? null,
                'default_currency' => $account->default_currency ?? null,
                'charges_enabled' => $account->charges_enabled ?? false,
                'payouts_enabled' => $account->payouts_enabled ?? false,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function getConnectedAccountsSummary(): array
    {
        $totalOrgs = Organization::query()->count();
        $connectedOrgs = Organization::query()->whereNotNull('stripe_account_id')->count();
        $onboardedOrgs = Organization::query()->where('stripe_onboarded', true)->count();

        return [
            'total_organizations' => $totalOrgs,
            'connected' => $connectedOrgs,
            'onboarded' => $onboardedOrgs,
            'pending_onboarding' => $connectedOrgs - $onboardedOrgs,
        ];
    }

    public function getConfigStatus(): array
    {
        return [
            'secret_key' => (bool) config('services.stripe.secret'),
            'webhook_secret' => (bool) config('services.stripe.webhook_secret'),
            'connect_client_id' => (bool) config('services.stripe.connect_client_id'),
            'redirect_uri' => route('stripe.connect.callback'),
            'processing_fee_percent' => (float) config('services.stripe.processing_fee_percent', 2.5),
        ];
    }

    public function getPlatformRevenue(): array
    {
        $totalFees = ProcessingFee::query()
            ->selectRaw('COALESCE(SUM(fee_amount), 0) as total')
            ->value('total');

        $paidFees = ProcessingFee::query()
            ->where('status', 'paid')
            ->selectRaw('COALESCE(SUM(fee_amount), 0) as total')
            ->value('total');

        $pendingFees = ProcessingFee::query()
            ->where('status', 'pending')
            ->selectRaw('COALESCE(SUM(fee_amount), 0) as total')
            ->value('total');

        return [
            'total' => (float) $totalFees,
            'paid' => (float) $paidFees,
            'pending' => (float) $pendingFees,
        ];
    }
}
