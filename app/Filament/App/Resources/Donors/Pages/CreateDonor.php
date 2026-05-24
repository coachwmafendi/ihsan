<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Filament\App\Resources\Donors\DonorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDonor extends CreateRecord
{
    protected static string $resource = DonorResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
