<?php

namespace App\Filament\App\Resources\Donations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DonationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Donation Details')
                    ->columns(['md' => 2, 'xl' => 3])
                    ->schema([
                        Select::make('campaign_id')
                            ->label('Campaign')
                            ->relationship('campaign', 'title')
                            ->disabled()
                            ->columnSpan(['md' => 2, 'xl' => 3]),
                        Select::make('donor_id')
                            ->label('Supporter')
                            ->relationship('donor', 'name')
                            ->disabled()
                            ->columnSpan(['md' => 2, 'xl' => 3]),
                        TextInput::make('gross_amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('MYR')
                            ->disabled(),
                        TextInput::make('stripe_fee')
                            ->numeric()
                            ->prefix('MYR')
                            ->disabled(),
                        TextInput::make('processing_fee')
                            ->numeric()
                            ->prefix('MYR')
                            ->disabled(),
                        TextInput::make('net_amount')
                            ->numeric()
                            ->prefix('MYR')
                            ->disabled(),
                        Select::make('type')
                            ->options([
                                'one_time' => 'One Time',
                                'recurring' => 'Recurring',
                            ])
                            ->disabled(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'succeeded' => 'Succeeded',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->disabled(),
                        TextInput::make('refunded_at')
                            ->label('Refunded At')
                            ->disabled()
                            ->visible(fn ($record) => $record?->refunded_at !== null),
                        Toggle::make('is_anonymous')
                            ->label('Anonymous')
                            ->disabled(),
                        TextInput::make('currency')
                            ->disabled(),
                    ]),
                Section::make('Donor Message')
                    ->schema([
                        Textarea::make('donor_message')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
                Section::make('Stripe Information')
                    ->columns(['md' => 2])
                    ->schema([
                        Fieldset::make('Payment IDs')
                            ->columns(2)
                            ->schema([
                                TextInput::make('stripe_payment_intent_id')
                                    ->label('Payment Intent ID')
                                    ->disabled()
                                    ->copyable(),
                                TextInput::make('stripe_charge_id')
                                    ->label('Charge ID')
                                    ->disabled()
                                    ->copyable(),
                                TextInput::make('subscription_id')
                                    ->label('Subscription ID')
                                    ->disabled()
                                    ->visible(fn ($record) => $record?->subscription_id !== null)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
