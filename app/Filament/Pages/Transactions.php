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

    public string $dateRange = 'all';

    public function setDateRange(string $range): void
    {
        $this->dateRange = $range;
    }

    public function getTotalsProperty(): array
    {
        $base = $this->getFilteredTableQuery();

        $amount = (float) (clone $base)->selectRaw(
            'COALESCE(SUM(COALESCE(base_amount, gross_amount)), 0) as total'
        )->value('total');

        $fee = (float) (clone $base)->sum('processing_fee');

        $orgReceives = (float) (clone $base)->sum('net_amount');

        return [
            'amount' => $amount,
            'fee' => $fee,
            'org_receives' => $orgReceives,
        ];
    }

    public function getActivePeriodLabelProperty(): string
    {
        return match ($this->dateRange) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7_days' => 'Last 7 days',
            '14_days' => 'Last 14 days',
            '30_days' => 'Last 30 days',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            default => 'All time',
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = Donation::query()->with(['campaign.organization', 'donor']);

                return match ($this->dateRange) {
                    'today' => $query->whereDate('created_at', today()),
                    'yesterday' => $query->whereDate('created_at', today()->subDay()),
                    '7_days' => $query->whereDate('created_at', '>=', today()->subDays(7)),
                    '14_days' => $query->whereDate('created_at', '>=', today()->subDays(14)),
                    '30_days' => $query->whereDate('created_at', '>=', today()->subDays(30)),
                    'this_month' => $query->whereMonth('created_at', today()->month)->whereYear('created_at', today()->year),
                    'last_month' => $query->whereMonth('created_at', today()->subMonth()->month)->whereYear('created_at', today()->subMonth()->year),
                    default => $query,
                };
            })
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
                    ->toggleable()
                    ->limit(30)
                    ->tooltip(fn (Donation $record): string => $record->campaign?->title ?? ''),
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
                    ->formatStateUsing(function (string $state, Donation $record): string {
                        $currency = strtoupper($record->currency);

                        return $currency.' '.number_format((float) $state, 2);
                    })
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
