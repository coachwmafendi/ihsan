<?php

namespace App\Filament\App\Resources\Subscriptions\Schemas;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Details')
                    ->columns(2)
                    ->schema([
                        Select::make('campaign_id')
                            ->label('Campaign')
                            ->relationship('campaign', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('donor_id')
                            ->label('Supporter')
                            ->relationship('donor', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('MYR'),
                        Select::make('interval')
                            ->required()
                            ->options(SubscriptionInterval::class),
                        Select::make('status')
                            ->required()
                            ->options(SubscriptionStatus::class),
                        TextInput::make('stripe_subscription_id')
                            ->label('Stripe Subscription ID')
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn ($record) => $record !== null),
                        TextInput::make('stripe_price_id')
                            ->label('Stripe Price ID')
                            ->disabled()
                            ->dehydrated()
                            ->visible(fn ($record) => $record !== null),
                    ]),
                Section::make('Billing Period')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('current_period_start')
                            ->label('Period Start'),
                        DatePicker::make('current_period_end')
                            ->label('Period End'),
                        DatePicker::make('paused_until')
                            ->label('Paused Until'),
                        DatePicker::make('cancelled_at')
                            ->label('Cancelled At'),
                    ]),
            ]);
    }
}
