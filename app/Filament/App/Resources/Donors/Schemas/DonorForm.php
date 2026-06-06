<?php

namespace App\Filament\App\Resources\Donors\Schemas;

use App\Models\Donor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
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
                        View::make('filament.app.resources.donors.partials.donor-information')
                            ->viewData(fn (?Donor $record): array => [
                                'donor' => $record,
                            ]),
                    ]),
            ]);
    }
}
