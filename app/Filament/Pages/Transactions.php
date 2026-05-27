<?php

namespace App\Filament\Pages;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Donation;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Transactions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.transactions';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?int $navigationSort = 15;

    public function table(Table $table): Table
    {
        return $table
            ->query(Donation::query()->with(['campaign.organization', 'donor']))
            ->columns([
                TextColumn::make('donor.name')
                    ->label('Donor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaign.organization.name')
                    ->label('Organisation')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('gross_amount')
                    ->label('Amount')
                    ->formatStateUsing(function (string $state, Donation $record): string {
                        if ($record->currency !== 'myr' && $record->base_amount !== null) {
                            return '≈ MYR '.number_format((float) $record->base_amount, 2);
                        }

                        return 'MYR '.number_format((float) $state, 2);
                    })
                    ->tooltip(function (string $state, Donation $record): ?string {
                        if ($record->currency !== 'myr' && $record->base_amount !== null) {
                            $gross = number_format((float) $state, 2);
                            $currency = strtoupper($record->currency);

                            return $currency.' '.$gross;
                        }

                        return null;
                    })
                    ->sortable(),
                TextColumn::make('processing_fee')
                    ->label('Fee')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->toggleable(),
                TextColumn::make('net_amount')
                    ->label('Org receives')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DonationStatus $state): string => str($state->value)->headline()->toString())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label('Organisation')
                    ->relationship('campaign.organization', 'name')
                    ->searchable(),
                SelectFilter::make('period')
                    ->label('Period')
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        '7_days' => '7 Days',
                        '14_days' => '14 Days',
                        '30_days' => '30 Days',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereDate('created_at', today()),
                            'yesterday' => $query->whereDate('created_at', today()->subDay()),
                            '7_days' => $query->whereDate('created_at', '>=', today()->subDays(7)),
                            '14_days' => $query->whereDate('created_at', '>=', today()->subDays(14)),
                            '30_days' => $query->whereDate('created_at', '>=', today()->subDays(30)),
                            'this_month' => $query->whereMonth('created_at', today()->month)->whereYear('created_at', today()->year),
                            'last_month' => $query->whereMonth('created_at', today()->subMonth()->month)->whereYear('created_at', today()->subMonth()->year),
                            default => $query,
                        };
                    }),
                SelectFilter::make('status')
                    ->options(DonationStatus::class),
                SelectFilter::make('type')
                    ->options(DonationType::class),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From'),
                        DatePicker::make('date_to')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['date_to'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'From: '.Carbon::parse($data['date_from'])->toFormattedDateString();
                        }

                        if ($data['date_to'] ?? null) {
                            $indicators[] = 'To: '.Carbon::parse($data['date_to'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                Filter::make('amount_range')
                    ->form([
                        TextInput::make('min_amount')
                            ->label('Min Amount')
                            ->numeric()
                            ->prefix('MYR'),
                        TextInput::make('max_amount')
                            ->label('Max Amount')
                            ->numeric()
                            ->prefix('MYR'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'] ?? null,
                                fn (Builder $query, $amount): Builder => $query->where('gross_amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'] ?? null,
                                fn (Builder $query, $amount): Builder => $query->where('gross_amount', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['min_amount'] ?? null) {
                            $indicators[] = 'Min: MYR '.number_format((float) $data['min_amount'], 2);
                        }

                        if ($data['max_amount'] ?? null) {
                            $indicators[] = 'Max: MYR '.number_format((float) $data['max_amount'], 2);
                        }

                        return $indicators;
                    }),
                Filter::make('is_anonymous')
                    ->form([
                        Toggle::make('is_anonymous')
                            ->label('Anonymous only'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['is_anonymous'] ?? false,
                            fn (Builder $query): Builder => $query->where('is_anonymous', true),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['is_anonymous'] ?? false) {
                            return 'Anonymous';
                        }

                        return null;
                    }),
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'visa' => 'Visa',
                        'mastercard' => 'Mastercard',
                        'amex' => 'American Express',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $brand): Builder => $query->where('payment_method_brand', $brand),
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
