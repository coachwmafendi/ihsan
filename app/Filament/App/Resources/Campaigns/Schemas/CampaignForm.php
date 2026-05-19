<?php

namespace App\Filament\App\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Campaign Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug())),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->required()
                            ->options(CampaignStatus::class),
                        DatePicker::make('end_date'),
                    ]),
                Section::make('Description & Media')
                    ->schema([
                        Textarea::make('description')
                            ->rows(5)
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->image()
                            ->directory('campaigns')
                            ->columnSpanFull(),
                    ]),
                Section::make('Fundraising')
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_target')
                            ->label('Set a fundraising target'),
                        TextInput::make('target_amount')
                            ->numeric()
                            ->prefix('MYR')
                            ->hidden(fn ($get) => ! $get('has_target')),
                        Toggle::make('allow_recurring')
                            ->label('Allow recurring donations'),
                        Repeater::make('suggested_amounts')
                            ->label('Suggested donation amounts')
                            ->schema([
                                TextInput::make('amount')
                                    ->numeric()
                                    ->prefix('MYR')
                                    ->required(),
                                TextInput::make('label')
                                    ->maxLength(100),
                            ])
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
