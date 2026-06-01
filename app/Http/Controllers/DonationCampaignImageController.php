<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Element;
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
        abort_unless(Storage::disk()->exists($imagePath), 404);

        return Storage::disk()->response($imagePath);
    }

    public function campaignImage(Campaign $campaign): StreamedResponse
    {
        abort_if($campaign->status !== CampaignStatus::Active, 404);

        $imagePath = $campaign->image_path;

        abort_if(blank($imagePath) || str($imagePath)->contains('..'), 404);
        abort_unless(Storage::disk()->exists($imagePath), 404);

        return Storage::disk()->response($imagePath);
    }
}
