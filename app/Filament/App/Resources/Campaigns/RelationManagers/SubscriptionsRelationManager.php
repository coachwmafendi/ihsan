<?php

namespace App\Filament\App\Resources\Campaigns\RelationManagers;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('donor_id')
                    ->relationship('donor', 'name')
                    ->required(),
                TextInput::make('stripe_subscription_id')
                    ->required()
                    ->disabled(),
                TextInput::make('stripe_price_id')
                    ->disabled(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->disabled(),
                TextInput::make('currency')
                    ->required()
                    ->default('myr')
                    ->disabled(),
                Select::make('interval')
                    ->options(SubscriptionInterval::class)
                    ->required()
                    ->disabled(),
                Select::make('status')
                    ->options(SubscriptionStatus::class)
                    ->default('incomplete')
                    ->required()
                    ->disabled(),
                TextInput::make('retry_count')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                DateTimePicker::make('current_period_start')
                    ->disabled(),
                DateTimePicker::make('current_period_end')
                    ->disabled(),
                DateTimePicker::make('paused_until')
                    ->disabled(),
                DateTimePicker::make('cancelled_at')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('donor.name')
                    ->searchable(),
                TextColumn::make('stripe_subscription_id')
                    ->searchable(),
                TextColumn::make('stripe_price_id')
                    ->searchable(),
                TextColumn::make('amount')
                    ->formatStateUsing(function ($record) {
                        $symbol = \App\Support\Currency::symbol($record->currency);

                        return $symbol.' '.number_format((float) $record->amount, 2);
                    })
                    ->sortable(),
                TextColumn::make('currency')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->searchable(),
                TextColumn::make('interval')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionInterval $state): string => str($state->value)->headline()->toString())
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionStatus $state): string => str($state->value)->headline()->toString())
                    ->searchable(),
                TextColumn::make('retry_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_period_start')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('current_period_end')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('paused_until')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cancelled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AssociateAction::make(),
            ])
            ->recordActions([
                DissociateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                ]),
            ]);
    }
}
