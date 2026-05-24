<?php

namespace App\Filament\App\Resources\Campaigns\RelationManagers;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DonationsRelationManager extends RelationManager
{
    protected static string $relationship = 'donations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('donor_id')
                    ->relationship('donor', 'name')
                    ->required(),
                Select::make('subscription_id')
                    ->relationship('subscription', 'id'),
                TextInput::make('stripe_payment_intent_id'),
                TextInput::make('stripe_charge_id'),
                TextInput::make('gross_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('stripe_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('processing_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('net_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('currency')
                    ->required()
                    ->default('myr'),
                Select::make('status')
                    ->options(DonationStatus::class)
                    ->default('pending')
                    ->required(),
                Select::make('type')
                    ->options(DonationType::class)
                    ->default('one_time')
                    ->required(),
                Textarea::make('donor_message')
                    ->columnSpanFull(),
                Toggle::make('is_anonymous')
                    ->required(),
                Textarea::make('utm_params')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('donor.name')
                    ->label('Supporter')
                    ->searchable()
                    ->limit(20),
                TextColumn::make('gross_amount')
                    ->label('Amount')
                    ->money('MYR')
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DonationStatus $state): string => str($state->value)->headline()->toString())
                    ->searchable()
                    ->colors([
                        'success' => 'succeeded',
                        'warning' => 'pending',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                    ]),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString())
                    ->searchable()
                    ->colors([
                        'primary' => 'recurring',
                        'gray' => 'one_time',
                    ]),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_anonymous')
                    ->label('Anonymous')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('net_amount')
                    ->label('Net')
                    ->money('MYR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
