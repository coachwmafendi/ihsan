<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailLogResponsivePreviewController extends Controller
{
    public function show(Donor $donor, DonorEmailLog $emailLog): View
    {
        $org = Auth::user()?->organization;

        if (! $org instanceof Organization) {
            abort(404);
        }

        if ($emailLog->donor_id !== $donor->getKey() || $emailLog->organization_id !== $org->getKey()) {
            abort(404);
        }

        $html = app(PreviewDonorEmail::class)->handle($emailLog);

        if ($html === null) {
            abort(404);
        }

        return view('email-logs.responsive-preview', [
            'emailLog' => $emailLog,
            'html' => $html,
        ]);
    }
}
