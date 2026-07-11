<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\OrganizationStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderableColumns()
            ->deferColumnManager(false)
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrganizationStatus $state): string => str($state->value)->headline()->toString())
                    ->searchable()
                    ->sortable(),
                IconColumn::make('stripe_onboarded')
                    ->boolean()
                    ->label('Stripe'),
                TextColumn::make('approved_at')
                    ->label('Onboarded Date')
                    ->dateTime('d M Y, h:i A', timezone: 'Asia/Kuala_Lumpur')
                    ->formatStateUsing(fn ($state) => $state ? myrTime($state) : '—')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Application Date')
                    ->dateTime('d M Y, h:i A', timezone: 'Asia/Kuala_Lumpur')
                    ->formatStateUsing(fn ($state) => $state ? myrTime($state) : '—')
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('d M Y, h:i A', timezone: 'Asia/Kuala_Lumpur')
                    ->formatStateUsing(fn ($state) => $state ? myrTime($state) : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrganizationStatus::class)
                    ->default('pending'),
                SelectFilter::make('registration_type')
                    ->options([
                        'ros' => 'ROS',
                        'rob' => 'ROB',
                        'others' => 'Others',
                    ]),
                TernaryFilter::make('stripe_onboarded')
                    ->label('Stripe Onboarded')
                    ->placeholder('All')
                    ->trueLabel('Onboarded')
                    ->falseLabel('Not Onboarded'),
                TernaryFilter::make('has_users')
                    ->label('Has Users')
                    ->placeholder('All')
                    ->trueLabel('Has Users')
                    ->falseLabel('No Users')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->has('users'),
                        false: fn (Builder $query): Builder => $query->doesntHave('users'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
