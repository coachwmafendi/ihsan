<?php

namespace App\Filament\App\Resources\Campaigns\Pages;

use App\Filament\App\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected string $view = 'filament.app.resources.campaigns.pages.list-campaigns';

    public function hasCampaigns(): bool
    {
        return Campaign::where('organization_id', auth()->user()->organization_id)->exists();
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
