<?php

namespace App\Filament\App\Resources\Campaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\App\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

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
                    <button
                        type="button"
                        x-data
                        @click="
                            navigator.clipboard.writeText('{$publicId}');
                            \$el.querySelector('.copy-icon').classList.add('hidden');
                            \$el.querySelector('.check-icon').classList.remove('hidden');
                            setTimeout(() => {
                                \$el.querySelector('.copy-icon').classList.remove('hidden');
                                \$el.querySelector('.check-icon').classList.add('hidden');
                            }, 2000);
                        "
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-400"
                        title="Copy ID"
                    >
                        <svg class="copy-icon w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <svg class="check-icon hidden w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        HTML);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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
