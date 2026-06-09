<?php

namespace App\Filament\App\Resources\Campaigns\Pages;

use App\Enums\ElementType;
use App\Filament\App\Resources\Campaigns\CampaignResource;
use App\Models\Element;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Str;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected string $view = 'filament.app.resources.campaigns.pages.create-campaign';

    protected Width|string|null $maxContentWidth = '7xl';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organization_id'] = auth()->user()->organization_id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $campaign = $this->record;
        $orgId = $campaign->organization_id;

        $hasExistingPortalButton = Element::query()
            ->where('organization_id', $orgId)
            ->where('is_donor_portal_default', true)
            ->exists();

        if ($hasExistingPortalButton) {
            return;
        }

        Element::create([
            'organization_id' => $orgId,
            'campaign_id' => $campaign->getKey(),
            'name' => 'Dedicated Donorportal Button',
            'token' => Str::random(6),
            'type' => ElementType::Button,
            'config' => [
                'button_text' => 'Make a new donation',
                'button_color' => 'bg-blue-600 hover:bg-blue-700',
                'button_size' => 'text-base px-6 py-3',
                'corner_radius' => 8,
                'button_effect' => 'none',
                'action' => 'checkout_modal',
            ],
            'is_active' => true,
            'is_donor_portal_default' => true,
        ]);
    }

    protected function beforeValidate(): void
    {
        $description = $this->data['description'] ?? '';

        $text = static::extractText($description);
        $wordCount = str_word_count(strip_tags($text));

        if ($wordCount > 100) {
            $this->addError('data.description', 'Description cannot exceed 100 words.');
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
