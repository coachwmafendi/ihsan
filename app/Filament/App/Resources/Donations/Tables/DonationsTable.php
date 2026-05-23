<?php

namespace App\Filament\App\Resources\Donations\Tables;

use App\Enums\DonationType;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DonationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('gross_amount')
                    ->label('Donation')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->icon(fn ($record): string => match ($record->payment_method_type) {
                        'card' => 'heroicon-o-credit-card',
                        'fpx' => 'heroicon-o-building-library',
                        'grabpay' => 'heroicon-o-device-phone-mobile',
                        'wallet' => 'heroicon-o-wallet',
                        default => 'heroicon-o-credit-card',
                    })
                    ->iconColor(fn ($record): string => match ($record->payment_method_type) {
                        'fpx' => 'info',
                        'grabpay' => 'success',
                        default => 'gray',
                    })
                    ->iconPosition(IconPosition::After)
                    ->sortable(),
                TextColumn::make('donor.name')
                    ->label('Supporter')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Frequency')
                    ->badge()
                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString())
                    ->color(fn (DonationType $state): string => match ($state) {
                        DonationType::Recurring => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_count')
                    ->label('Payments')
                    ->getStateUsing(fn ($record): string => $record->type === DonationType::Recurring
                        ? (string) ($record->subscription?->payment_count ?? 0)
                        : '—')
                    ->sortable(),
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
                                TextEntry::make('id')
                                    ->label('Donation ID'),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime('d M Y, h:i A'),
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
                                    ->label('Frequency')
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
                        Section::make('Stripe Transaction')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('stripe_payment_intent_id')
                                    ->label('Payment Intent ID')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('stripe_charge_id')
                                    ->label('Charge ID')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('payment_method_brand')
                                    ->label('Payment Method'),
                                TextEntry::make('payment_method_type')
                                    ->label('Method Type'),
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
