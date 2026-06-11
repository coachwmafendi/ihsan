<?php

namespace App\Filament\App\Resources\Donations\Tables;

use App\Actions\Stripe\RefundDonation;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Filament\Exports\DonationExporter;
use App\Models\Campaign;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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
                            'fpx' => 'heroicon-o-building-library',
                            'grabpay' => 'heroicon-o-device-phone-mobile',
                            'apple_pay' => 'heroicon-o-wallet',
                            'google_pay' => 'heroicon-o-wallet',
                            default => 'heroicon-o-credit-card',
                        };

                        $paymentLabel = match ($record->payment_method_type) {
                            'fpx' => 'FPX',
                            'grabpay' => 'GrabPay',
                            'apple_pay' => 'Apple Pay',
                            'google_pay' => 'Google Pay',
                            default => 'Card',
                        };

                        $iconColor = match ($record->payment_method_type) {
                            'fpx' => 'text-blue-500',
                            'grabpay' => 'text-green-500',
                            'apple_pay' => 'text-gray-800',
                            'google_pay' => 'text-gray-800',
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
                        Select::make('preset')
                            ->label('Quick select')
                            ->options([
                                'today' => 'Today',
                                'yesterday' => 'Yesterday',
                                'last_7_days' => 'Last 7 days',
                                'last_30_days' => 'Last 30 days',
                                'this_month' => 'This month',
                                'this_year' => 'This year',
                                'last_month' => 'Last month',
                                'last_year' => 'Last year',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state === null) {
                                    $set('from', null);
                                    $set('until', null);

                                    return;
                                }

                                $now = now();
                                [$from, $until] = match ($state) {
                                    'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                                    'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
                                    'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
                                    'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
                                    'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                                    'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
                                    'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
                                    'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
                                    default => [null, null],
                                };

                                $set('from', $from?->format('Y-m-d'));
                                $set('until', $until?->format('Y-m-d'));
                            }),
                        DatePicker::make('from')->label('From')->live(),
                        DatePicker::make('until')->label('Until')->live(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if ($from && $until) {
                            return 'Date: '.Carbon::parse($from)->format('d M Y').' – '.Carbon::parse($until)->format('d M Y');
                        }

                        if ($from) {
                            return 'From: '.Carbon::parse($from)->format('d M Y');
                        }

                        if ($until) {
                            return 'Until: '.Carbon::parse($until)->format('d M Y');
                        }

                        return null;
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(DonationExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->fileName(fn (): string => 'donations-'.now()->format('Y-m-d'))
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'succeeded')),
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
            ]);
    }
}
