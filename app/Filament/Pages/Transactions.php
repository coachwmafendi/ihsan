<?php

namespace App\Filament\Pages;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Donation;
use App\Models\Organization;
use App\Support\ReportingPeriod;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Transactions extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.transactions';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?int $navigationSort = 15;

    public string $quickPeriod = '';

    public string $quickStatus = '';

    public string $quickType = '';

    public string $quickOrganization = '';

    public string $advancedDateFrom = '';

    public string $advancedDateTo = '';

    public string $advancedMinAmount = '';

    public string $advancedMaxAmount = '';

    public bool $advancedIsAnonymous = false;

    public ?string $advancedPaymentMethod = null;

    public bool $filtersApplied = false;

    public function getOrganizationOptionsProperty(): Collection
    {
        return Organization::query()
            ->whereHas('campaigns.donations')
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function applyFilters(
        string $period = '',
        string $status = '',
        string $type = '',
        string $organization = '',
        string $dateFrom = '',
        string $dateTo = '',
        string $minAmount = '',
        string $maxAmount = '',
        bool $isAnonymous = false,
        ?string $paymentMethod = null,
    ): void {
        $this->quickPeriod = $period;
        $this->quickStatus = $status;
        $this->quickType = $type;
        $this->quickOrganization = $organization;
        $this->advancedDateFrom = $dateFrom;
        $this->advancedDateTo = $dateTo;
        $this->advancedMinAmount = $minAmount;
        $this->advancedMaxAmount = $maxAmount;
        $this->advancedIsAnonymous = $isAnonymous;
        $this->advancedPaymentMethod = $paymentMethod;
        $this->filtersApplied = true;

        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->quickPeriod = '';
        $this->quickStatus = '';
        $this->quickType = '';
        $this->quickOrganization = '';
        $this->advancedDateFrom = '';
        $this->advancedDateTo = '';
        $this->advancedMinAmount = '';
        $this->advancedMaxAmount = '';
        $this->advancedIsAnonymous = false;
        $this->advancedPaymentMethod = null;
        $this->filtersApplied = false;

        $this->resetPage();
    }

    public function getTotalsProperty(): array
    {
        $base = $this->getFilteredTableQuery();

        $amount = (float) (clone $base)->selectRaw(
            'COALESCE(SUM(COALESCE(base_amount, gross_amount)), 0) as total'
        )->value('total');

        // processing_fee and net_amount are already stored in MYR for every currency
        $fee = (float) (clone $base)->selectRaw(
            'COALESCE(SUM(processing_fee), 0) as total'
        )->value('total');

        $orgReceives = (float) (clone $base)->selectRaw(
            'COALESCE(SUM(net_amount), 0) as total'
        )->value('total');

        return [
            'amount' => $amount,
            'fee' => $fee,
            'org_receives' => $orgReceives,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = Donation::query()->with(['campaign.organization', 'donor']);

                if (! $this->filtersApplied) {
                    return $query;
                }

                // Dates are entered as Malaysian ones; the column is UTC.
                if ($this->advancedDateFrom) {
                    $query->where('created_at', '>=', ReportingPeriod::parseLocalDate($this->advancedDateFrom)->startOfDay()->utc());
                }

                if ($this->advancedDateTo) {
                    $query->where('created_at', '<=', ReportingPeriod::parseLocalDate($this->advancedDateTo)->endOfDay()->utc());
                }

                if ($this->advancedMinAmount !== '') {
                    $query->where('gross_amount', '>=', (float) $this->advancedMinAmount);
                }

                if ($this->advancedMaxAmount !== '') {
                    $query->where('gross_amount', '<=', (float) $this->advancedMaxAmount);
                }

                if ($this->advancedIsAnonymous) {
                    $query->where('is_anonymous', true);
                }

                if ($this->advancedPaymentMethod) {
                    $query->where('payment_method_brand', $this->advancedPaymentMethod);
                }

                if ($this->quickPeriod) {
                    [$periodFrom, $periodTo] = ReportingPeriod::utc(match ($this->quickPeriod) {
                        '7_days' => '7_days',
                        '14_days' => '14_days',
                        '30_days' => '30_days',
                        default => $this->quickPeriod,
                    });

                    $query
                        ->when($periodFrom, fn (Builder $q): Builder => $q->where('created_at', '>=', $periodFrom))
                        ->when($periodTo, fn (Builder $q): Builder => $q->where('created_at', '<=', $periodTo));
                }

                if ($this->quickStatus) {
                    $query->where('status', $this->quickStatus);
                }

                if ($this->quickType) {
                    $query->where('type', $this->quickType);
                }

                if ($this->quickOrganization) {
                    $query->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $this->quickOrganization));
                }

                return $query;
            })
            ->columns([
                TextColumn::make('donor.name')
                    ->label('Donor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaign.organization.name')
                    ->label('Organization')
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
                        $display = $record->displayAmount((float) $state);

                        if (strtolower($record->currency) !== 'myr' && $record->base_amount !== null) {
                            return '≈ MYR '.number_format((float) $record->base_amount, 2);
                        }

                        return $display;
                    })
                    ->tooltip(function (string $state, Donation $record): ?string {
                        if (strtolower($record->currency) !== 'myr' && $record->base_amount !== null) {
                            return $record->displayAmount((float) $state);
                        }

                        return null;
                    })
                    ->sortable(),
                TextColumn::make('processing_fee')
                    ->label('Processing Fee')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->tooltip(function (string $state, Donation $record): ?string {
                        $exchangeRate = (float) ($record->exchange_rate ?? 0);

                        if ($record->currency !== 'myr' && $exchangeRate > 0) {
                            return '≈ '.strtoupper($record->currency).' '.number_format((float) $state / $exchangeRate, 2);
                        }

                        return null;
                    })
                    ->toggleable(),
                TextColumn::make('net_amount')
                    ->label('Org receives')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->toggleable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (DonationType $state): string => match ($state) {
                        DonationType::OneTime => 'gray',
                        DonationType::Recurring => 'info',
                    })
                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (DonationStatus $state): string => match ($state) {
                        DonationStatus::Pending => 'warning',
                        DonationStatus::Succeeded => 'success',
                        DonationStatus::Failed => 'danger',
                        DonationStatus::Cancelled => 'gray',
                        DonationStatus::Refunded => 'info',
                    })
                    ->formatStateUsing(fn (DonationStatus $state): string => str($state->value)->headline()->toString())
                    ->tooltip(fn (Donation $record): ?string => $record->status_tooltip)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A', timezone: 'Asia/Kuala_Lumpur')
                    ->formatStateUsing(fn ($state) => $state ? myrTime($state) : '—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
