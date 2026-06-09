<?php

namespace App\Filament\App\Resources\Elements\Pages;

use App\Filament\App\Resources\Elements\ElementResource;
use App\Models\Element;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListElements extends ListRecords
{
    protected static string $resource = ElementResource::class;

    protected string $view = 'filament.app.resources.elements.pages.list-elements';

    public function hasElements(): bool
    {
        return Element::where('organization_id', auth()->user()->organization_id)->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
