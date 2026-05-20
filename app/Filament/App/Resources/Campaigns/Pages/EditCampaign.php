<?php

namespace App\Filament\App\Resources\Campaigns\Pages;

use App\Filament\App\Resources\Campaigns\CampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
