<?php

namespace App\Filament\App\Resources\Donors\Tables;

use App\Filament\App\Resources\Donors\DonorResource;
use App\Filament\Exports\DonorExporter;
use App\Models\Donation;
use App\Models\Donor;
use App\Support\Currency;
use Carbon\Carbon;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DonorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderableColumns()
            ->deferColumnManager(false)
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withAggregate('donations', 'created_at', 'min')
                ->withAggregate('donations', 'created_at', 'max')
                ->selectSub(
                    Donation::whereColumn('donor_id', 'donors.id')
                        ->selectRaw('COALESCE(SUM(gross_amount), 0)'),
                    'lifetime_total_myr'
                )
                ->selectSub(
                    Donation::whereColumn('donor_id', 'donors.id')
                        ->selectRaw('currency')
                        ->groupBy('currency')
                        ->orderByRaw('COUNT(*) DESC')
                        ->limit(1),
                    'primary_currency'
                ))
            ->recordUrl(fn (Donor $record): string => DonorResource::getUrl('view', ['record' => DonorResource::getRecordUrlKey($record)]))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('lifetime_total_myr')
                    ->label('Lifetime Donated')
                    ->formatStateUsing(function ($state, $record): string {
                        $currency = $record->primary_currency ?? 'myr';

                        return Currency::symbol($currency).' '.number_format((float) $state, 2);
                    })
                    ->tooltip(function ($record): ?string {
                        $breakdown = $record->donations()
                            ->selectRaw('currency, SUM(gross_amount) as total_original')
                            ->groupBy('currency')
                            ->get();

                        if ($breakdown->count() < 2) {
                            return null;
                        }

                        return $breakdown
                            ->map(fn ($d) => strtoupper($d->currency).' '.number_format((float) $d->total_original, 2))
                            ->implode(' + ');
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('donations_min_created_at')
                    ->label('First Donation')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('donations_max_created_at')
                    ->label('Last Donation')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('donations_max_created_at', 'desc')
            ->filters([
                Filter::make('donation_date')
                    ->label('Date')
                    ->form([
                        Select::make('preset')
                            ->label('Quick select')
                            ->options([
                                'today' => 'Today',
                                'yesterday' => 'Yesterday',
                                'last_7_days' => 'Last 7 days',
                                'last_14_days' => 'Last 14 days',
                                'last_30_days' => 'Last 30 days',
                                'this_week' => 'This week',
                                'this_month' => 'This month',
                                'this_year' => 'This year',
                                'last_week' => 'Last week',
                                'last_month' => 'Last month',
                                'last_year' => 'Last year',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state === null) {
                                    $set('start_date', null);
                                    $set('end_date', null);

                                    return;
                                }

                                $now = now();
                                $dates = match ($state) {
                                    'today' => [$now->startOfDay(), $now->endOfDay()],
                                    'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
                                    'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->endOfDay()],
                                    'last_14_days' => [$now->copy()->subDays(13)->startOfDay(), $now->endOfDay()],
                                    'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->endOfDay()],
                                    'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                                    'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                                    'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
                                    'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
                                    'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
                                    'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
                                    default => [null, null],
                                };

                                $set('start_date', $dates[0]?->format('Y-m-d'));
                                $set('end_date', $dates[1]?->format('Y-m-d'));
                            }),
                        DatePicker::make('start_date')
                            ->label('Start date')
                            ->live(),
                        DatePicker::make('end_date')
                            ->label('End date')
                            ->live(),
                    ])
                    ->query(function ($query, array $data) {
                        $startDate = $data['start_date'] ?? null;
                        $endDate = $data['end_date'] ?? null;

                        if ($startDate === null && $endDate === null) {
                            return;
                        }

                        $query->whereHas('donations', function ($q) use ($startDate, $endDate) {
                            if ($startDate) {
                                $q->whereDate('created_at', '>=', $startDate);
                            }
                            if ($endDate) {
                                $q->whereDate('created_at', '<=', $endDate);
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $startDate = $data['start_date'] ?? null;
                        $endDate = $data['end_date'] ?? null;

                        if ($startDate === null && $endDate === null) {
                            return null;
                        }

                        if ($startDate && $endDate) {
                            return 'Donations: '.Carbon::parse($startDate)->format('M d, Y').' - '.Carbon::parse($endDate)->format('M d, Y');
                        }

                        if ($startDate) {
                            return 'Donations from: '.Carbon::parse($startDate)->format('M d, Y');
                        }

                        return 'Donations until: '.Carbon::parse($endDate)->format('M d, Y');
                    }),
                Filter::make('has_donations')
                    ->label('Has donations')
                    ->query(fn ($query) => $query->whereHas('donations')),
                Filter::make('has_subscriptions')
                    ->label('Has recurring')
                    ->query(fn ($query) => $query->whereHas('subscriptions')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(DonorExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->fileName(fn (): string => 'donors-'.now()->format('Y-m-d')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Donor $record): string => DonorResource::getUrl('view', ['record' => DonorResource::getRecordUrlKey($record)])),
            ])
            ;
    }
}
