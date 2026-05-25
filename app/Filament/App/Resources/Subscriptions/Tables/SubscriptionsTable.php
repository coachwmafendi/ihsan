<?php

namespace App\Filament\App\Resources\Subscriptions\Tables;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use Filament\Actions\BulkActionGroup;
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
                    ->label('Date')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionStatus $state): string => str($state->value)->headline()->toString())
                    ->color(fn (SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::Active => 'success',
                        SubscriptionStatus::Paused => 'warning',
                        SubscriptionStatus::Cancelled => 'gray',
                        SubscriptionStatus::PastDue => 'danger',
                        SubscriptionStatus::Incomplete => 'info',
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('donations_count')
                    ->label('Installments')
                    ->counts('donations')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('donations_sum_gross_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'MYR '.number_format((float) ($state ?? 0), 2))
                    ->sum('donations', 'gross_amount')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('donor.name')
                    ->label('Supporter')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(SubscriptionStatus::cases())
                        ->mapWithKeys(fn (SubscriptionStatus $case) => [
                            $case->value => str($case->value)->headline()->toString(),
                        ])
                        ->toArray()),
                SelectFilter::make('interval')
                    ->options(SubscriptionInterval::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
