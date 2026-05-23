<?php

namespace App\Filament\App\Resources\Campaigns\Pages;

use App\Filament\App\Resources\Campaigns\CampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected Width|string|null $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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
