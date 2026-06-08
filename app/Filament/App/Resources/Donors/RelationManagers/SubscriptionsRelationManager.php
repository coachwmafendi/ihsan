<?php

namespace App\Filament\App\Resources\Donors\RelationManagers;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $recordTitleAttribute = 'amount';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-arrow-path';

    public function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('<span class="flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>Recurring Plans</span>'))
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereHas('campaign', fn (Builder $campaignQuery) => $campaignQuery
                    ->where('organization_id', auth()->user()->organization_id)))
            ->columns([
                TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Start Date'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (string $state, $record): string => $record->currency_symbol.' '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('interval')
                    ->label('Frequency')
                    ->badge()
                    ->formatStateUsing(fn (SubscriptionInterval $state): string => str($state->value)->headline()->toString())
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->getStateUsing(fn ($record): string => ucfirst($record->status->value))
                    ->color(fn ($state): string => match ((string) $state) {
                        'Active' => 'success',
                        'Past Due' => 'warning',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('donations_count')
                    ->label('Installments')
                    ->counts('donations')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SubscriptionStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn ($record): string => route('filament.app.resources.subscriptions.view', $record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
