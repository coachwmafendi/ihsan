<?php

namespace App\Filament\App\Resources\Donors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class DonorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('donations_count')
                    ->counts('donations')
                    ->label('Donations')
                    ->sortable(),
                TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('Recurring')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('has_donations')
                    ->label('Has donations')
                    ->query(fn ($query) => $query->whereHas('donations')),
                Filter::make('has_subscriptions')
                    ->label('Has recurring')
                    ->query(fn ($query) => $query->whereHas('subscriptions')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
