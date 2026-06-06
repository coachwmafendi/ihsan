<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Filament\App\Resources\Donors\DonorResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDonor extends ViewRecord
{
    protected static string $resource = DonorResource::class;

    protected string $view = 'filament.app.resources.donors.pages.view-donor';

    public function hasFormWrapper(): bool
    {
        return false;
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
