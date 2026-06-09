<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Filament\App\Resources\Donors\DonorResource;
use App\Models\Donor;
use Filament\Resources\Pages\ListRecords;

class ListDonors extends ListRecords
{
    protected static string $resource = DonorResource::class;

    protected string $view = 'filament.app.resources.donors.pages.list-donors';

    public function hasSupporters(): bool
    {
        return Donor::whereHas('donations.campaign', fn ($q) => $q->where('organization_id', auth()->user()->organization_id))
            ->exists();
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
