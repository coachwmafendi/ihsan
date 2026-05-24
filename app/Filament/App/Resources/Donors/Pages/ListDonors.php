<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Filament\App\Resources\Donors\DonorResource;
use Filament\Resources\Pages\ListRecords;

class ListDonors extends ListRecords
{
    protected static string $resource = DonorResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
