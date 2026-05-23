<?php

namespace App\Filament\App\Resources\Campaigns\Tables;

use App\Enums\CampaignStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->status->name)
                    ->color(fn ($state): string => match ((string) $state) {
                        'Draft' => 'gray',
                        'Active' => 'success',
                        'Paused' => 'warning',
                        'Ended' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('collected_amount')
                    ->label('Raised')
                    ->formatStateUsing(fn (string $state): string => 'MYR '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('target_amount')
                    ->label('Target')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? 'No target' : 'MYR '.number_format((float) $state, 2))
                    ->sortable(),
                IconColumn::make('allow_recurring')
                    ->boolean()
                    ->label('Recurring'),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CampaignStatus::class),
                Filter::make('has_target')
                    ->query(fn (Builder $query) => $query->where('has_target', true)),
                Filter::make('allow_recurring')
                    ->query(fn (Builder $query) => $query->where('allow_recurring', true)),
                Filter::make('created_at')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at', '<=', $d))),
                SelectFilter::make('end_date')
                    ->label('Ending')
                    ->options([
                        'ending_soon' => 'Ending Soon',
                        'ended' => 'Ended',
                        'no_end' => 'No End Date',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'ending_soon' => $query->whereBetween('end_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()]),
                        'ended' => $query->where('end_date', '<', now()->startOfDay()),
                        'no_end' => $query->whereNull('end_date'),
                        default => $query,
                    }),
                Filter::make('collected_amount')
                    ->label('Collected Range')
                    ->form([
                        TextInput::make('min')
                            ->label('Min (MYR)')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('max')
                            ->label('Max (MYR)')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['min'] ?? null, fn (Builder $q, $m): Builder => $q->where('collected_amount', '>=', (float) $m))
                        ->when($data['max'] ?? null, fn (Builder $q, $m): Builder => $q->where('collected_amount', '<=', (float) $m))),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function ($record) {
                        $replica = $record->replicate();
                        $replica->title = $record->title.' (Copy)';
                        $replica->collected_amount = 0;
                        $replica->save();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
