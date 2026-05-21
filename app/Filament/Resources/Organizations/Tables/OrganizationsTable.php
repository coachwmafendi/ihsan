<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\OrganizationStatus;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->options(OrganizationStatus::class),
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
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From'),
                        DatePicker::make('date_to')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['date_to'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'From: '.Carbon::parse($data['date_from'])->toFormattedDateString();
                        }

                        if ($data['date_to'] ?? null) {
                            $indicators[] = 'To: '.Carbon::parse($data['date_to'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                Filter::make('has_users')
                    ->label('Has Users')
                    ->query(fn (Builder $query): Builder => $query->has('users'))
                    ->indicateUsing(fn (): ?string => 'Has Users'),
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
