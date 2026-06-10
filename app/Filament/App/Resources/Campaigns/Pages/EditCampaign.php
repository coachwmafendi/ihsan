<?php

namespace App\Filament\App\Resources\Campaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\App\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected string $view = 'filament.app.resources.campaigns.pages.edit-campaign';

    protected Width|string|null $maxContentWidth = '7xl';

    public function getHeading(): string|Htmlable
    {
        $campaign = $this->record;
        $publicId = $campaign->public_id;

        return new HtmlString(<<<HTML
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {$campaign->title}
                </h1>
                <div class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-gray-700 dark:text-gray-300">ID</span>
                    <span>{$publicId}</span>
                    <x-ui.copy-button value="{$publicId}" />
                </div>
            </div>
        HTML);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function archiveCampaign(): void
    {
        $this->record->update(['status' => CampaignStatus::Archived]);

        Notification::make()
            ->title('Campaign archived')
            ->body('The campaign has been archived successfully.')
            ->success()
            ->send();

        $this->redirect(route('filament.app.resources.campaigns.index'));
    }

    public function duplicateCampaign(): void
    {
        /** @var Campaign $replica */
        $replica = $this->record->replicate([
            'public_id',
            'collected_amount',
            'form_parameter',
        ]);
        $replica->public_id = null;
        $replica->title = $this->record->title.' (Copy)';
        $replica->collected_amount = 0;
        $replica->form_parameter = null;
        $replica->status = CampaignStatus::Draft;
        $replica->save();

        Notification::make()
            ->title('Campaign duplicated')
            ->body('A copy of the campaign has been created.')
            ->success()
            ->send();

        $this->redirect(route('filament.app.resources.campaigns.edit', $replica));
    }

    public function deleteCampaign(): void
    {
        $this->record->delete();

        Notification::make()
            ->title('Campaign deleted')
            ->body('The campaign has been deleted.')
            ->success()
            ->send();

        $this->redirect(route('filament.app.resources.campaigns.index'));
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
