<?php

namespace App\Filament\App\Resources\Subscriptions\Tables;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('donations_count')
                    ->label('Installments')
                    ->counts('donations')
                    ->sortable(),
                TextColumn::make('donations_sum_gross_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'MYR '.number_format((float) ($state ?? 0), 2))
                    ->sum('donations', 'gross_amount')
                    ->sortable(),
                TextColumn::make('donor.name')
                    ->label('Supporter')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SubscriptionStatus::class),
                SelectFilter::make('interval')
                    ->options(SubscriptionInterval::class),
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
