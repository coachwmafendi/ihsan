<?php

namespace App\Filament\App\Resources\Elements\Pages;

use App\Filament\App\Resources\Elements\ElementResource;
use App\Filament\App\Resources\Elements\Schemas\ElementForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateElement extends CreateRecord
{
    protected static string $resource = ElementResource::class;

    protected string $view = 'filament.app.resources.elements.pages.create-element';

    protected function getFormActions(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = auth()->user()->organization_id;
        $data['token'] = $data['token'] ?? Str::random(6);
        $data['config'] = [
            ...ElementForm::defaultConfigForType($data['type'] ?? null),
            ...($data['config'] ?? []),
        ];

        return $data;
    }
}
