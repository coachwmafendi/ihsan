<?php

namespace App\Filament\App\Resources\Campaigns\Pages;

use App\Filament\App\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected Width|string|null $maxContentWidth = '7xl';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = auth()->user()->organization_id;

        return $data;
    }

    protected function beforeValidate(): void
    {
        $description = $this->data['description'] ?? '';

        $text = static::extractText($description);
        $wordCount = str_word_count(strip_tags($text));

        if ($wordCount > 100) {
            $this->addError('data.description', 'Penerangan tidak boleh melebihi 100 patah perkataan.');
            $this->halt();
        }
    }

    private static function extractText(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (! is_array($content)) {
            return '';
        }

        if (isset($content['text'])) {
            return $content['text'];
        }

        $texts = [];

        if (isset($content['content'])) {
            foreach ($content['content'] as $node) {
                $texts[] = static::extractText($node);
            }
        }

        return implode(' ', $texts);
    }
}
