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
            ->reorderableColumns()
            ->deferColumnManager(false)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Date')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
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
                    ->formatStateUsing(function ($state, $record): string {
                        $formatted = $record->currency_symbol.' '.number_format((float) $state, 2);

                        if ($record->currency !== 'myr') {
                            $rate = $record->donations()
                                ->whereNotNull('exchange_rate')
                                ->value('exchange_rate');

                            if ($rate) {
                                $myr = (float) $state * (float) $rate;
                                $formatted .= ' (≈ MYR '.number_format($myr, 2).')';
                            }
                        }

                        return $formatted;
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('donations_count')
                    ->label('Installments')
                    ->counts('donations')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('donations_sum_gross_amount')
                    ->label('Total')
                    ->sum('donations', 'gross_amount')
                    ->formatStateUsing(fn ($state, $record): string => $record->currency_symbol.' '.number_format((float) ($state ?? 0), 2))
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
                    ->options(SubscriptionStatus::class),
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
