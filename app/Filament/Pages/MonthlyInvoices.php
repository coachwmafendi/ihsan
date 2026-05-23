<?php

namespace App\Filament\Pages;

use App\Models\MonthlyInvoice;
use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class MonthlyInvoices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Monthly Invoices';

    protected static ?int $navigationSort = 19;

    public string $totalOutstanding = '0.00';

    public string $totalCollected = '0.00';

    public int $invoicesSent = 0;

    public function mount(): void
    {
        $this->totalOutstanding = number_format((float) MonthlyInvoice::query()
            ->whereIn('stripe_status', ['open', 'uncollectible'])
            ->sum('total_fees'), 2, '.', '');

        $this->totalCollected = number_format((float) MonthlyInvoice::query()
            ->where('stripe_status', 'paid')
            ->sum('total_fees'), 2, '.', '');

        $this->invoicesSent = MonthlyInvoice::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(MonthlyInvoice::query()->with('organization'))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization.name')
                    ->label('NGO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period')
                    ->label('Period')
                    ->date('F Y')
                    ->sortable(),
                TextColumn::make('total_fees')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('stripe_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'open' => 'warning',
                        'uncollectible' => 'danger',
                        'void' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->headline()->toString()),
                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('stripe_invoice_id')
                    ->label('Stripe ID')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label('NGO')
                    ->options(Organization::pluck('name', 'id')->toArray()),
                SelectFilter::make('stripe_status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'paid' => 'Paid',
                        'uncollectible' => 'Uncollectible',
                        'void' => 'Void',
                    ]),
                Filter::make('period')
                    ->form([
                        DatePicker::make('period_from')->label('From'),
                        DatePicker::make('period_to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['period_from'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('period', '>=', $d))
                            ->when($data['period_to'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('period', '<=', $d));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('view_stripe')
                    ->label('View in Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MonthlyInvoice $record): ?string => $record->stripe_invoice_url)
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-sparkles')
                    ->action(function () {
                        $exitCode = Artisan::call('ihsan:generate-monthly-invoices');

                        Notification::make()
                            ->title($exitCode === 0 ? 'Invoices generated successfully.' : 'Invoice generation failed. Check logs.')
                            ->success($exitCode === 0)
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generate Monthly Invoices')
                    ->modalDescription('This will create Stripe Invoices for all pending platform fees from the previous month. Continue?'),
            ]);
    }
}
