<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Element;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationCampaignImageController extends Controller
{
    public function __invoke(Element $element): StreamedResponse
    {
        abort_if(! $element->is_active, 404);

        $element->loadMissing('campaign');

        $imagePath = $element->campaign?->image_path;

        abort_if(blank($imagePath) || str($imagePath)->contains('..'), 404);
        $disk = $this->imageDisk($imagePath);

        abort_if($disk === null, 404);

        return Storage::disk($disk)->response($imagePath);
    }

    public function campaignImage(Campaign $campaign): StreamedResponse
    {
        abort_if($campaign->status !== CampaignStatus::Active, 404);

        $imagePath = $campaign->image_path;

        abort_if(blank($imagePath) || str($imagePath)->contains('..'), 404);
        $disk = $this->imageDisk($imagePath);

        abort_if($disk === null, 404);

        return Storage::disk($disk)->response($imagePath);
    }

    private function imageDisk(string $imagePath): ?string
    {
        $disks = Arr::whereNotNull([
            'local',
            'public',
            config('filesystems.default'),
        ]);

        foreach (array_unique($disks) as $disk) {
            if (Storage::disk($disk)->exists($imagePath)) {
                return $disk;
            }
        }

        return null;
    }
}
