<?php

namespace App\Filament\App\Resources\Donations\Tables;

use App\Enums\DonationType;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

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
                    ->formatStateUsing(function ($state, $record): HtmlString {
                        $isForeign = $record->currency !== 'myr';
                        $gross = number_format((float) $state, 2);
                        $currency = strtoupper($record->currency);

                        if ($isForeign && $record->base_amount) {
                            $amount = '≈ MYR '.number_format((float) $record->base_amount, 2);
                        } else {
                            $amount = 'MYR '.$gross;
                        }

                        $paymentIcon = match ($record->payment_method_type) {
                            'card' => 'heroicon-o-credit-card',
                            'fpx' => 'heroicon-o-building-library',
                            'grabpay' => 'heroicon-o-device-phone-mobile',
                            'wallet' => 'heroicon-o-wallet',
                            default => 'heroicon-o-credit-card',
                        };

                        $paymentLabel = match ($record->payment_method_type) {
                            'card' => 'Card',
                            'fpx' => 'FPX',
                            'grabpay' => 'GrabPay',
                            'wallet' => 'Wallet',
                            default => 'Card',
                        };

                        $iconColor = match ($record->payment_method_type) {
                            'fpx' => 'text-blue-500',
                            'grabpay' => 'text-green-500',
                            default => 'text-gray-400',
                        };

                        $result = $amount;
                        $result .= '&nbsp;&nbsp;&nbsp;'.Blade::render('<x-'.$paymentIcon.' title="'.$paymentLabel.'" class="inline-block size-4 '.$iconColor.'" />');

                        if ($record->type === DonationType::Recurring) {
                            $result .= '&nbsp;&nbsp;&nbsp;&nbsp;'.Blade::render('<x-heroicon-o-arrow-path title="Recurring" class="inline-block size-4 text-blue-500" />');
                            $result .= '&nbsp;'.($record->subscription?->payment_count ?? 0);
                        }

                        return new HtmlString($result);
                    })
                    ->tooltip(function ($state, $record): ?string {
                        if ($record->currency !== 'myr' && $record->base_amount) {
                            $gross = number_format((float) $state, 2);
                            $currency = strtoupper($record->currency);

                            return $currency.' '.$gross;
                        }

                        return null;
                    })
                    ->sortable(),
                TextColumn::make('donor.name')
                    ->label('Supporter')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record): HtmlString {
                        $deviceIcon = match ($record->device_type) {
                            'mobile' => 'heroicon-o-device-phone-mobile',
                            'tablet' => 'heroicon-o-device-tablet',
                            'desktop' => 'heroicon-o-computer-desktop',
                            default => null,
                        };

                        $deviceLabel = match ($record->device_type) {
                            'mobile' => 'Mobile donation',
                            'tablet' => 'Tablet donation',
                            'desktop' => 'Desktop donation',
                            default => null,
                        };

                        $iconColor = 'text-gray-400';

                        $result = e($state);

                        if ($deviceIcon && $deviceLabel) {
                            $result .= ' <span title="'.$deviceLabel.'">'.Blade::render('<x-'.$deviceIcon.' class="inline-block size-3.5 '.$iconColor.'" />').'</span>';
                        }

                        return new HtmlString($result);
                    }),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
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
                Filter::make('created_at')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '<=', $d));
                    }),
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
                                TextEntry::make('donor.name')
                                    ->label('Supporter')
                                    ->formatStateUsing(function ($state, $record): HtmlString {
                                        $icon = match ($record->device_type) {
                                            'mobile' => 'heroicon-o-device-phone-mobile',
                                            'tablet' => 'heroicon-o-device-tablet',
                                            'desktop' => 'heroicon-o-computer-desktop',
                                            default => null,
                                        };

                                        $label = match ($record->device_type) {
                                            'mobile' => 'Mobile donation',
                                            'tablet' => 'Tablet donation',
                                            'desktop' => 'Desktop donation',
                                            default => null,
                                        };

                                        $result = e($state);

                                        if ($icon && $label) {
                                            $result .= ' <span title="'.$label.'">'.Blade::render('<x-'.$icon.' class="inline-block size-4 text-gray-400" />').'</span>';
                                        }

                                        return new HtmlString($result);
                                    }),
                                TextEntry::make('donor.email')
                                    ->label('Email')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('donor.phone')
                                    ->label('Phone')
                                    ->placeholder('—'),
                                TextEntry::make('campaign.title')
                                    ->label('Campaign'),
                                TextEntry::make('page_url')
                                    ->label('URL')
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('element_token')
                                    ->label('Element (Type-Name)')
                                    ->columnSpanFull()
                                    ->visible(fn ($record): bool => is_array($record->utm_params) && ($record->utm_params['source'] ?? null) === 'element')
                                    ->formatStateUsing(function ($record): string {
                                        $utm = is_array($record->utm_params) ? $record->utm_params : [];
                                        $type = $utm['element_type'] ?? '';
                                        $name = $utm['element_name'] ?? '';

                                        return ucwords(str_replace('_', ' ', $type)).' - '.($name ?: '—');
                                    }),
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
                                TextEntry::make('currency')
                                    ->label('Currency')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                                TextEntry::make('is_anonymous')
                                    ->label('Anonymous')
                                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                                TextEntry::make('payment_method_brand')
                                    ->label('Payment Method')
                                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->headline()->toString() : '—'),
                                TextEntry::make('payment_method_type')
                                    ->label('Method Type')
                                    ->formatStateUsing(fn (?string $state): string => $state === 'fpx' ? 'FPX' : (str($state ?? '')->headline()->toString() ?: '—')),
                                TextEntry::make('payment_method_last4')
                                    ->label('Card Number')
                                    ->formatStateUsing(fn (?string $state): string => $state ? '•••• '.$state : '—'),
                                TextEntry::make('donor_message')
                                    ->label('Message')
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->donor_message !== null),
                            ]),
                        Section::make('Client Info')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('ip_address')
                                    ->label('IP Address')
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('browser')
                                    ->label('Browser')
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('os')
                                    ->label('OS')
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('device_type')
                                    ->label('Device')
                                    ->formatStateUsing(function (?string $state): string {
                                        return match ($state) {
                                            'mobile' => 'Mobile',
                                            'tablet' => 'Tablet',
                                            'desktop' => 'Desktop',
                                            default => '—',
                                        };
                                    })
                                    ->icon(fn (?string $state): ?string => match ($state) {
                                        'mobile' => 'heroicon-o-device-phone-mobile',
                                        'tablet' => 'heroicon-o-device-tablet',
                                        'desktop' => 'heroicon-o-computer-desktop',
                                        default => null,
                                    }),
                                TextEntry::make('geo_city')
                                    ->label('City')
                                    ->formatStateUsing(function (?string $state, $record): string {
                                        if ($state && $record->geo_region) {
                                            return $state.', '.$record->geo_region;
                                        }

                                        return $state ?? '—';
                                    }),
                                TextEntry::make('geo_region')
                                    ->label('Region')
                                    ->visible(fn ($record) => $record->geo_city === null && $record->geo_region !== null)
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
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
                            ]),
                        Section::make('Payment & fees')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('gross_amount')
                                    ->label('Gross')
                                    ->icon('heroicon-o-currency-dollar')
                                    ->formatStateUsing(function ($state, $record): string {
                                        if ($record->currency !== 'myr' && $record->base_amount) {
                                            return '≈ MYR '.number_format((float) $record->base_amount, 2);
                                        }

                                        return 'MYR '.number_format((float) $state, 2);
                                    })
                                    ->tooltip(function ($state, $record): ?string {
                                        if ($record->currency !== 'myr' && $record->base_amount) {
                                            return strtoupper($record->currency).' '.number_format((float) $state, 2);
                                        }

                                        return null;
                                    }),
                                TextEntry::make('stripe_fee')
                                    ->label('Stripe Fee')
                                    ->icon('heroicon-o-credit-card')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('processing_fee')
                                    ->label('Processing Fee')
                                    ->icon('heroicon-o-receipt-percent')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('donor_fee_covered')
                                    ->label('Fee Covered')
                                    ->icon('heroicon-o-heart')
                                    ->formatStateUsing(function ($state): string {
                                        $amount = (float) ($state ?? 0);

                                        if ($amount > 0) {
                                            return 'Covered · MYR '.number_format($amount, 2);
                                        }

                                        return 'Not Covered';
                                    })
                                    ->color(fn ($state): string => (float) ($state ?? 0) > 0 ? 'success' : 'gray')
                                    ->badge(),
                                TextEntry::make('effective_fee')
                                    ->label('Effective Fee')
                                    ->icon('heroicon-o-arrows-right-left')
                                    ->getStateUsing(function ($record): float {
                                        return (float) ($record->stripe_fee ?? 0) + (float) ($record->processing_fee ?? 0);
                                    })
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('net_amount')
                                    ->label('Net')
                                    ->icon('heroicon-o-check-badge')
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
