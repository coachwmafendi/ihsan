<?php

namespace App\Filament\App\Resources\Donors\RelationManagers;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $recordTitleAttribute = 'amount';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Start Date'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state, $record): string => $record->currency_symbol.' '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('interval')
                    ->label('Frequency')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionInterval $state): string => str($state->value)->headline()->toString())
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->getStateUsing(fn ($record): string => ucfirst($record->status->value))
                    ->color(fn ($state): string => match ((string) $state) {
                        'Active' => 'success',
                        'Past Due' => 'warning',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('donations_count')
                    ->label('Installments')
                    ->counts('donations')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SubscriptionStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->url(fn ($record): string => route('filament.app.resources.subscriptions.edit', $record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
