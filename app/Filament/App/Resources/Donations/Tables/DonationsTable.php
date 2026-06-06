<?php

namespace App\Filament\App\Resources\Donations\Tables;

use App\Actions\Stripe\RefundDonation;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
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
            ->reorderableColumns()
            ->deferColumnManager(false)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('gross_amount')
                    ->label('Donation')
                    ->formatStateUsing(function ($state, $record): HtmlString {
                        $gross = number_format((float) $state, 2);
                        $currency = strtoupper($record->currency);
                        $isForeign = $currency !== 'MYR';

                        if ($isForeign && $record->base_amount) {
                            $amount = '≈ MYR '.number_format((float) $record->base_amount, 2);
                        } else {
                            $amount = $currency.' '.$gross;
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
                        if ($record->currency !== 'myr') {
                            return strtoupper($record->currency).' '.number_format((float) $state, 2);
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
                TextColumn::make('element_label')
                    ->label('Element')
                    ->placeholder('—')
                    ->toggleable(),
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
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
