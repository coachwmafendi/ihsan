<?php

namespace App\Filament\App\Resources\Donors\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class DonorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('donations_sum_gross_amount')
                    ->label('Lifetime Donated')
                    ->sum('donations', 'gross_amount')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('first_donation')
                    ->label('First Donation')
                    ->getStateUsing(fn ($record) => $record->donations()->oldest('created_at')->first()?->created_at)
                    ->date()
                    ->sortable(query: fn ($query, $direction) => $query->withAggregate('donations', 'created_at', 'min')->orderBy('donations_min_created_at', $direction)),
                TextColumn::make('last_donation')
                    ->label('Last Donation')
                    ->getStateUsing(fn ($record) => $record->donations()->latest('created_at')->first()?->created_at)
                    ->date()
                    ->sortable(query: fn ($query, $direction) => $query->withAggregate('donations', 'created_at', 'max')->orderBy('donations_max_created_at', $direction)),
            ])
            ->defaultSort('last_donation', 'desc')
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
