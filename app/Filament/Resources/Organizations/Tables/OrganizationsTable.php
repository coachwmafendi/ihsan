<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\OrganizationStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderableColumns()
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
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
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
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
