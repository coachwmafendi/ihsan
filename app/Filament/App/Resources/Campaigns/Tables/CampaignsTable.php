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
                TextColumn::make('progress')
                    ->label('Progress')
                    ->html()
                    ->getStateUsing(function ($record): string {
                        if (! $record->has_target || ! $record->target_amount > 0) {
                            return '<span class="text-xs text-gray-400">No target</span>';
                        }

                        $pct = min(100, (int) round(($record->collected_amount / $record->target_amount) * 100));
                        $barColor = $pct >= 100 ? 'bg-emerald-600' : ($pct >= 50 ? 'bg-teal-600' : 'bg-amber-500');

                        return '<div class="flex items-center gap-2 w-44"><div class="flex-1 h-2.5 rounded-full bg-gray-200"><div class="h-2.5 rounded-full '.$barColor.'" style="width: '.$pct.'%"></div></div><span class="text-xs font-medium tabular-nums text-gray-600">'.$pct.'%<br><span class="text-gray-400 font-normal">of MYR '.number_format((float) $record->target_amount, 2).'</span></span></div>';
                    }),
                TextColumn::make('donations_count')
                    ->label('Donors')
                    ->counts('donations')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('latestDonation.created_at')
                    ->label('Last Donation')
                    ->formatStateUsing(fn ($state): string => $state?->diffForHumans() ?? 'No donations yet')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('allow_recurring')
                    ->boolean()
                    ->label('Recurring')
                    ->toggleable(isToggledHiddenByDefault: true),
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
