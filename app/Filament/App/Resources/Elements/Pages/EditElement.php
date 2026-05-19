<?php

namespace App\Filament\App\Resources\Elements\Pages;

use App\Filament\App\Resources\Elements\ElementResource;
use App\Filament\App\Resources\Elements\Schemas\ElementForm;
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

    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['config'] = [
            ...ElementForm::defaultConfigForType($data['type'] ?? null),
            ...($data['config'] ?? []),
        ];

        return $data;
    }
}
