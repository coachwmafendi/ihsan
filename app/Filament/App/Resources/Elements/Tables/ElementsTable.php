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
use Illuminate\Contracts\View\View;

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
                    ->formatStateUsing(fn (ElementType $state): string => match ($state->value) {
                        'qr_code' => 'QR Code',
                        default => str($state->value)->replace('_', ' ')->title(),
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->placeholder('Any campaign')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('token')
                    ->label('Embed Code')
                    ->action(null)
                    ->formatStateUsing(fn ($record): ?View => $record ? view('filament.tables.columns.embed-code-column', ['record' => $record]) : null)
                    ->extraAttributes(['class' => 'max-w-md']),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'button' => 'Button',
                        'floating_button' => 'Floating Button',
                        'form' => 'Form',
                        'popup' => 'Popup',
                        'qr_code' => 'QR Code',
                        'link' => 'Link',
                    ]),
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
