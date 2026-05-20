<?php

namespace App\Filament\App\Resources\Donors\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DonorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Supporter Information')
                    ->columns(['md' => 2])
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['md' => 2]),
                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(['md' => 2]),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ]),
            ]);
    }
}
