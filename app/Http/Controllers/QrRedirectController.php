<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Models\Element;
use Illuminate\View\View;

class QrRedirectController extends Controller
{
    public function show(Element $element): View
    {
        abort_if(
            ! $element->is_active
                || $element->isArchived()
                || $element->campaign === null
                || $element->campaign->status !== CampaignStatus::Active,
            404
        );

        return view('qr.redirect', [
            'element' => $element,
            'organization' => $element->campaign?->organization,
        ]);
    }
}
