<?php

namespace App\Filament\App\Resources\Donations\Tables;

use App\Enums\DonationType;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DonationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('gross_amount')
                    ->label('Donation')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('donor.name')
                    ->label('Supporter')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_method_brand')
                    ->label('Payment Method')
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === null || $record->payment_method_last4 === null) {
                            return '—';
                        }

                        return view('filament.tables.columns.payment-method', [
                            'brand' => $state,
                            'last4' => $record->payment_method_last4,
                        ])->render();
                    })->html(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->getStateUsing(fn ($record): string => ucfirst($record->status->value))
                    ->color(fn ($state): string => match ((string) $state) {
                        'Pending' => 'gray',
                        'Succeeded' => 'success',
                        'Failed' => 'danger',
                        'Refunded' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('is_anonymous')
                    ->boolean()
                    ->label('Anonymous')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('campaign_id')
                    ->label('Campaign')
                    ->options(fn () => Campaign::query()
                        ->where('organization_id', auth()->user()->organization_id)
                        ->pluck('title', 'id'))
                    ->searchable(),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'succeeded' => 'Succeeded',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->attribute('status'),
                SelectFilter::make('type')
                    ->options([
                        'one_time' => 'One Time',
                        'recurring' => 'Recurring',
                    ])
                    ->attribute('type'),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Donation Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema([
                        Section::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('donor.name')
                                    ->label('Supporter'),
                                TextEntry::make('campaign.title')
                                    ->label('Campaign'),
                                TextEntry::make('gross_amount')
                                    ->label('Amount')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('status')
                                    ->badge()
                                    ->getStateUsing(fn ($record): string => ucfirst($record->status->value))
                                    ->color(fn ($state): string => match ((string) $state) {
                                        'Pending' => 'gray',
                                        'Succeeded' => 'success',
                                        'Failed' => 'danger',
                                        'Refunded' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('type')
                                    ->badge()
                                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString()),
                                TextEntry::make('is_anonymous')
                                    ->label('Anonymous')
                                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                                TextEntry::make('donor_message')
                                    ->label('Message')
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->donor_message !== null),
                            ]),
                        Section::make('Financial Breakdown')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('gross_amount')
                                    ->label('Gross')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('stripe_fee')
                                    ->label('Stripe Fee')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('platform_fee')
                                    ->label('Platform Fee')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('net_amount')
                                    ->label('Net')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                            ]),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
