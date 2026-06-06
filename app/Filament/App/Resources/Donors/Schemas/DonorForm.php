<?php

namespace App\Filament\App\Resources\Donors\Schemas;

use App\Models\Donor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DonorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Information')
                    ->icon('heroicon-o-user')
                    ->extraAttributes(['id' => 'supporter-information', 'class' => 'scroll-mt-6'])
                    ->schema([
                        TextInput::make('name')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('email')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('locale')
                            ->label('Language')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'en' => 'English',
                                'ms' => 'Bahasa Melayu',
                                default => $state ?: '—',
                            }),

                        TextInput::make('phone')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),

                        Placeholder::make('mailing_address')
                            ->label('Mailing address')
                            ->content(function (?Donor $record): string {
                                if ($record === null) {
                                    return '—';
                                }

                                $parts = array_filter([
                                    $record->address_line1,
                                    $record->address_line2,
                                    $record->address_city,
                                    $record->address_state,
                                    $record->address_postal_code,
                                    $record->country,
                                ]);

                                return $parts ? implode(', ', $parts) : '—';
                            }),
                    ]),
            ]);
    }
}
