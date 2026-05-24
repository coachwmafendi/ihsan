<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Filament\App\Resources\Donors\DonorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDonor extends EditRecord
{
    protected static string $resource = DonorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
