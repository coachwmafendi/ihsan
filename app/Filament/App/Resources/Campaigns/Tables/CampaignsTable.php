<?php

namespace App\Filament\App\Resources\Campaigns\Tables;

use App\Enums\CampaignStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function ($record) {
                        $replica = $record->replicate();
                        $replica->title = $record->title.' (Copy)';
                        $replica->slug = $record->slug.'-copy';
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
