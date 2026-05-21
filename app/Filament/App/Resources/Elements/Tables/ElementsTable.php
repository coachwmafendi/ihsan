<?php

namespace App\Filament\App\Resources\Elements\Tables;

use App\Enums\ElementType;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ElementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (ElementType $state): string => ucfirst($state->value))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->placeholder('Any campaign')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('token')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ElementType::class),
                Filter::make('is_active')
                    ->label('Active only')
                    ->query(fn ($query) => $query->where('is_active', true)),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->is_active ? 'gray' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
