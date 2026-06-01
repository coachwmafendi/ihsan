<?php

namespace App\Filament\App\Resources\Donations\Tables;

use App\Actions\Stripe\RefundDonation;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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
                        $deviceType = $record->device_type;

                        $icon = match ($deviceType) {
                            'mobile' => 'heroicon-o-device-phone-mobile',
                            'tablet' => 'heroicon-o-device-tablet',
                            'desktop' => 'heroicon-o-computer-desktop',
                            default => null,
                        };

                        $label = match ($deviceType) {
                            'mobile' => 'Mobile',
                            'tablet' => 'Tablet',
                            'desktop' => 'Desktop',
                            default => null,
                        };

                        $result = e($state);

                        if ($icon && $label) {
                            $result .= ' <span title="'.$label.'">'.Blade::render('<x-'.$icon.' class="inline-block size-3.5 text-gray-400" />').'</span>';
                        }

                        return new HtmlString($result);
                    }),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('element_name')
                    ->label('Element')
                    ->getStateUsing(fn ($record): ?string => is_array($record->utm_params) && ($record->utm_params['source'] ?? null) === 'element'
                        ? ($record->utm_params['element_name'] ?? null)
                        : null)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->getStateUsing(fn ($record): string => ucfirst($record->status->value))
                    ->tooltip(fn ($record): ?string => $record->status->value === 'refunded' && $record->refunded_at
                        ? 'Refunded on '.$record->refunded_at->format('d M Y, h:i A')
                        : null)
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
                SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'element' => 'Element (Widget)',
                        'direct' => 'Direct',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->where('utm_params->source', $data['value'])
                        : $query),
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
                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Refund Donation')
                    ->modalDescription(fn ($record): string => 'Refund '.strtoupper($record->currency).' '.number_format((float) $record->gross_amount, 2).' to '.$record->donor?->name.'? This cannot be undone.')
                    ->modalSubmitActionLabel('Refund')
                    ->visible(fn ($record): bool => $record->status === DonationStatus::Succeeded)
                    ->action(function ($record): void {
                        try {
                            app(RefundDonation::class)->handle($record);

                            Notification::make()
                                ->title('Refund successful.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Refund failed: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Donation Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->extraModalFooterActions(fn ($record): array => $record->status === DonationStatus::Succeeded ? [
                        Action::make('refund_modal')
                            ->label('Refund')
                            ->color('danger')
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->requiresConfirmation()
                            ->modalHeading('Refund Donation')
                            ->modalDescription(fn () => 'Refund '.strtoupper($record->currency).' '.number_format((float) $record->gross_amount, 2).' to '.$record->donor?->name.'? This cannot be undone.')
                            ->modalSubmitActionLabel('Refund')
                            ->action(function ($livewire) use ($record): void {
                                try {
                                    app(RefundDonation::class)->handle($record);
                                    Notification::make()->title('Refund successful.')->success()->send();
                                    $livewire->redirect($livewire->getUrl());
                                } catch (\Exception $e) {
                                    Notification::make()->title('Refund failed: '.$e->getMessage())->danger()->send();
                                }
                            }),
                    ] : [])
                    ->schema([
                        // ── Hero ─────────────────────────────────────────
                        Section::make()
                            ->schema([
                                TextEntry::make('gross_amount')
                                    ->label('Amount')
                                    ->formatStateUsing(function ($state, $record): string {
                                        $gross = number_format((float) $state, 2);
                                        if ($record->currency !== 'myr' && $record->base_amount) {
                                            return strtoupper($record->currency).' '.$gross.' ≈ MYR '.number_format((float) $record->base_amount, 2);
                                        }

                                        return strtoupper($record->currency).' '.$gross;
                                    })
                                    ->weight('bold')
                                    ->size('xl'),
                                TextEntry::make('status')
                                    ->label('Status')
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
                                    ->label('Type')
                                    ->badge()
                                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString()),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime('d M Y, h:i A'),
                            ])
                            ->columns(4),

                        // ── Donor & Campaign ─────────────────────────────
                        Section::make('Donor & Campaign')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('donor.name')
                                    ->label('Supporter')
                                    ->formatStateUsing(function ($state, $record): HtmlString {
                                        $deviceType = $record->device_type;

                                        $icon = match ($deviceType) {
                                            'mobile' => 'heroicon-o-device-phone-mobile',
                                            'tablet' => 'heroicon-o-device-tablet',
                                            'desktop' => 'heroicon-o-computer-desktop',
                                            default => null,
                                        };

                                        $label = match ($deviceType) {
                                            'mobile' => 'Mobile',
                                            'tablet' => 'Tablet',
                                            'desktop' => 'Desktop',
                                            default => null,
                                        };

                                        $result = e($state);

                                        if ($icon && $label) {
                                            $result .= ' <span title="'.$label.'">'.Blade::render('<x-'.$icon.' class="inline-block size-4 text-gray-400" />').'</span>';
                                        }

                                        return new HtmlString($result);
                                    }),
                                TextEntry::make('campaign.title')
                                    ->label('Campaign'),
                                TextEntry::make('donor.email')
                                    ->label('Email')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('donor.phone')
                                    ->label('Phone')
                                    ->placeholder('—'),
                                TextEntry::make('payment_method_brand')
                                    ->label('Payment Method')
                                    ->formatStateUsing(fn (?string $state, $record): string => collect([
                                        $state ? str($state)->headline()->toString() : null,
                                        $record->payment_method_last4 ? '•••• '.$record->payment_method_last4 : null,
                                    ])->filter()->join(' ') ?: '—'),
                                TextEntry::make('is_anonymous')
                                    ->label('Anonymous')
                                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                                TextEntry::make('donor_message')
                                    ->label('Message')
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->donor_message !== null),
                                TextEntry::make('page_url')
                                    ->label('URL')
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('element_token')
                                    ->label('Element')
                                    ->columnSpanFull()
                                    ->visible(fn ($record): bool => is_array($record->utm_params) && ($record->utm_params['source'] ?? null) === 'element')
                                    ->formatStateUsing(function ($record): string {
                                        $utm = is_array($record->utm_params) ? $record->utm_params : [];

                                        return ucwords(str_replace('_', ' ', $utm['element_type'] ?? '')).' - '.($utm['element_name'] ?? '—');
                                    }),
                            ]),

                        // ── Receipt & Transaction ────────────────────────
                        Section::make('Receipt')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('invoice_number')
                                    ->label('Receipt No.')
                                    ->copyable()
                                    ->copyMessage('Copied')
                                    ->icon('heroicon-o-receipt-percent'),
                                TextEntry::make('id')
                                    ->label('Donation ID')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('receipt_download_link')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->visible(fn ($record): bool => $record->status->value === 'succeeded')
                                    ->getStateUsing(fn ($record): string => route('donations.receipt.download', $record))
                                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                                        '<a href="'.e($state).'" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:text-gray-900 transition-colors">'.
                                        Blade::render('<x-heroicon-o-arrow-down-tray class="size-3.5 text-gray-500" />').'Download Receipt</a>'
                                    )),
                            ]),

                        // ── Payment & Fees ───────────────────────────────
                        Section::make('Payment & Fees')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('gross_amount')
                                    ->label('Gross')
                                    ->formatStateUsing(function ($state, $record): string {
                                        $gross = number_format((float) $state, 2);
                                        if ($record->currency !== 'myr' && $record->base_amount) {
                                            return strtoupper($record->currency).' '.$gross.' ≈ MYR '.number_format((float) $record->base_amount, 2);
                                        }

                                        return strtoupper($record->currency).' '.$gross;
                                    }),
                                TextEntry::make('effective_fee')
                                    ->label('Total Fees')
                                    ->getStateUsing(fn ($record): float => (float) ($record->stripe_fee ?? 0) + (float) ($record->processing_fee ?? 0))
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('net_amount')
                                    ->label('Net')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('stripe_fee')
                                    ->label('Stripe Fee')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('processing_fee')
                                    ->label('Processing Fee')
                                    ->formatStateUsing(fn ($state) => 'MYR '.number_format((float) $state, 2)),
                                TextEntry::make('donor_fee_covered')
                                    ->label('Fee Covered by Donor')
                                    ->formatStateUsing(function ($state): string {
                                        $amount = (float) ($state ?? 0);

                                        return $amount > 0 ? 'MYR '.number_format($amount, 2) : 'No';
                                    })
                                    ->color(fn ($state): string => (float) ($state ?? 0) > 0 ? 'success' : 'gray')
                                    ->badge(),
                            ]),

                        // ── Client Info (collapsed) ──────────────────────
                        Section::make('Client Info')
                            ->collapsed()
                            ->columns(2)
                            ->schema([
                                TextEntry::make('ip_address')
                                    ->label('IP Address')
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—')
                                    ->copyable()
                                    ->copyMessage('Copied'),
                                TextEntry::make('device_type')
                                    ->label('Device')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'mobile' => 'Mobile',
                                        'tablet' => 'Tablet',
                                        'desktop' => 'Desktop',
                                        default => '—',
                                    })
                                    ->icon(fn (?string $state): ?string => match ($state) {
                                        'mobile' => 'heroicon-o-device-phone-mobile',
                                        'tablet' => 'heroicon-o-device-tablet',
                                        'desktop' => 'heroicon-o-computer-desktop',
                                        default => null,
                                    }),
                                TextEntry::make('browser')
                                    ->label('Browser')
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('os')
                                    ->label('OS')
                                    ->formatStateUsing(fn (?string $state): string => $state ?? '—'),
                                TextEntry::make('geo_city')
                                    ->label('Location')
                                    ->formatStateUsing(function (?string $state, $record): string {
                                        if ($state && $record->geo_region) {
                                            return $state.', '.$record->geo_region;
                                        }

                                        return $state ?? '—';
                                    }),
                            ]),

                        // ── Refund ───────────────────────────────────────
                        Section::make('Refund')
                            ->visible(fn ($record): bool => $record->status->value === 'refunded')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('refunded_at')
                                    ->label('Refunded At')
                                    ->dateTime('d M Y, h:i A'),
                            ]),

                        // ── Stripe Details (collapsed) ───────────────────
                        Section::make('Stripe Details')
                            ->collapsed()
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
