<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

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
            'platform_fee_percent' => Setting::get('stripe_platform_fee_percent', config('services.stripe.platform_fee_percent', 2.5)),
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
                TextInput::make('platform_fee_percent')
                    ->label('Platform Fee (%)')
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

        Setting::set('stripe_platform_fee_percent', (float) $data['platform_fee_percent']);

        config(['services.stripe.platform_fee_percent' => (float) $data['platform_fee_percent']]);

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
}
