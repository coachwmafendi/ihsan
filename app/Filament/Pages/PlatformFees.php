<?php

namespace App\Filament\Pages;

use App\Models\Organization;
use App\Models\PlatformFee;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformFees extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.platform-fees';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Platform Fees';

    protected static ?int $navigationSort = 18;

    public function table(Table $table): Table
    {
        return $table
            ->query(PlatformFee::query()->with(['donation.donor', 'donation.campaign', 'organization', 'monthlyInvoice']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('NGO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('donation.campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('donation.donor.name')
                    ->label('Donor')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('donation.gross_amount')
                    ->label('Donation')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('fee_amount')
                    ->label('Fee')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('fee_percentage')
                    ->label('Rate')
                    ->formatStateUsing(fn (string $state): string => $state.'%')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'invoiced' => 'info',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('monthlyInvoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label('NGO')
                    ->options(Organization::pluck('name', 'id')->toArray()),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'invoiced' => 'Invoiced',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ]),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('date_from')->label('From'),
                        DatePicker::make('date_to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '>=', $d))
                            ->when($data['date_to'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check')
                    ->visible(fn (PlatformFee $record): bool => $record->status === 'pending')
                    ->action(fn (PlatformFee $record) => $record->update(['status' => 'paid']))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([]);
    }
}
