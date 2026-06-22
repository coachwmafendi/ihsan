<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailLogResponsivePreviewController extends Controller
{
    public function show(DonorEmailLog $emailLog): View
    {
        $org = Auth::user()?->organization;

        if (! $org instanceof Organization || $emailLog->organization_id !== $org->getKey()) {
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
