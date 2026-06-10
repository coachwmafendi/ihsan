<?php

namespace App\Filament\App\Resources\Donors\RelationManagers;

use App\Enums\DonationType;
use App\Support\Currency;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class DonationsRelationManager extends RelationManager
{
    protected static string $relationship = 'donations';

    protected static ?string $recordTitleAttribute = 'gross_amount';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-currency-dollar';

    public function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('<span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>Donations</span>'))
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereHas('campaign', fn (Builder $campaignQuery) => $campaignQuery
                    ->where('organization_id', auth()->user()->organization_id)))
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('gross_amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state, $record): string => Currency::symbol($record->currency).' '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
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
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (DonationType $state): string => str($state->value)->headline()->toString())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn ($record): string => route('filament.app.resources.donations.view', $record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
