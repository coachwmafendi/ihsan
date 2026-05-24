<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationCampaignImageController extends Controller
{
    public function __invoke(Element $element): StreamedResponse
    {
        abort_if(
            ! $element->is_active || ! in_array($element->type, [ElementType::Form, ElementType::Popup], true),
            404
        );

        $element->loadMissing('campaign');

        $imagePath = $element->campaign?->image_path;

        abort_if(blank($imagePath) || str($imagePath)->contains('..'), 404);
        abort_unless(Storage::disk('local')->exists($imagePath), 404);

        return Storage::disk('local')->response($imagePath);
    }

    public function campaignImage(Campaign $campaign): StreamedResponse
    {
        abort_if($campaign->status !== CampaignStatus::Active, 404);

        $imagePath = $campaign->image_path;

        abort_if(blank($imagePath) || str($imagePath)->contains('..'), 404);
        abort_unless(Storage::disk('local')->exists($imagePath), 404);

        return Storage::disk('local')->response($imagePath);
    }
}
