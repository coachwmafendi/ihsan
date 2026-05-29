<?php

namespace App\Filament\App\Resources\Donors\RelationManagers;

use App\Enums\DonationType;
use App\Support\Currency;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
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
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Donation Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema([
                        Section::make('Donor & Campaign')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime('d M Y, h:i A'),
                                TextEntry::make('campaign.title')
                                    ->label('Campaign'),
                                TextEntry::make('gross_amount')
                                    ->label('Amount')
                                    ->columnSpanFull()
                                    ->formatStateUsing(function ($state, $record): string {
                                        $gross = number_format((float) $state, 2);
                                        if ($record->currency !== 'myr' && $record->base_amount) {
                                            $converted = number_format((float) $record->base_amount, 2);

                                            return strtoupper($record->currency).' '.$gross.' ≈ MYR '.$converted;
                                        }

                                        return 'MYR '.$gross;
                                    })
                                    ->weight('bold')
                                    ->size('lg'),
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
                                TextEntry::make('donor_message')
                                    ->label('Message')
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->donor_message !== null),
                            ]),
                        Section::make('Transaction')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('invoice_number')
                                    ->label('Receipt')
                                    ->copyable()
                                    ->copyMessage('Copied')
                                    ->icon('heroicon-o-receipt-percent')
                                    ->url(fn ($record): ?string => $record->status->value === 'succeeded' ? route('donations.receipt.download', $record) : null, shouldOpenInNewTab: true),
                                TextEntry::make('stripe_payment_intent_id')
                                    ->label('Payment Intent ID')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('stripe_charge_id')
                                    ->label('Charge ID')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('payment_method_brand')
                                    ->label('Payment Method')
                                    ->formatStateUsing(fn (?string $state, $record): string => collect([
                                        $state ? str($state)->headline()->toString() : null,
                                        $record->payment_method_last4 ? '•••• '.$record->payment_method_last4 : null,
                                    ])->filter()->join(' ') ?: '—'),
                            ]),
                        Section::make('Payment & Fees')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('gross_amount')
                                    ->label('Gross')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('stripe_fee')
                                    ->label('Stripe Fee')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('net_amount')
                                    ->label('Net')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                            ]),
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
