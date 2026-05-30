<?php

namespace App\Filament\App\Resources\Campaigns\RelationManagers;

use App\Enums\ElementType;
use App\Filament\App\Resources\Elements\Schemas\ElementForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ElementsRelationManager extends RelationManager
{
    protected static string $relationship = 'elements';

    public function form(Schema $schema): Schema
    {
        return ElementForm::configure($schema, [
            'hide_campaign' => true,
            'hide_submit' => true,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('organization.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('token')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (ElementType $state): string => match ($state->value) {
                        'qr_code' => 'QR Code',
                        default => str($state->value)->replace('_', ' ')->title(),
                    })
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->using(function (array $data) {
                        $campaign = $this->getOwnerRecord();

                        return $campaign->elements()->create([
                            'organization_id' => $campaign->organization_id,
                            'name' => $data['name'],
                            'type' => $data['type'],
                            'token' => Str::random(6),
                            'config' => $data['config'] ?? null,
                            'is_active' => $data['is_active'] ?? true,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
