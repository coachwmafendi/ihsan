<?php

namespace App\Filament\App\Resources\Elements\Pages;

use App\Filament\App\Resources\Elements\ElementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditElement extends EditRecord
{
    protected static string $resource = ElementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
