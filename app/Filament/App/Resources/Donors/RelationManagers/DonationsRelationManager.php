<?php

namespace App\Filament\App\Resources\Donors\RelationManagers;

use App\Enums\DonationType;
use App\Support\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DonationsRelationManager extends RelationManager
{
    protected static string $relationship = 'donations';

    protected static ?string $recordTitleAttribute = 'gross_amount';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('gross_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state, $record): string => Currency::symbol($record->currency).' '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->getStateUsing(fn ($record): string => ucfirst($record->status->value))
                    ->color(fn ($state): string => match ((string) $state) {
                        'Pending' => 'gray',
                        'Succeeded' => 'success',
                        'Failed' => 'danger',
                        'Refunded' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                //
            ])
            ->actions([
                ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Donation Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form([
                        TextInput::make('gross_amount')
                            ->label('Amount')
                            ->readOnly(),
                        TextInput::make('status')
                            ->label('Status')
                            ->readOnly(),
                        TextInput::make('campaign.title')
                            ->label('Campaign')
                            ->readOnly(),
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
