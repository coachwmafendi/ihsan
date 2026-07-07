<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReceiptDownloadController extends Controller
{
    public function __invoke(Donation $donation): Response
    {
        return $this->download($donation);
    }

    public function signed(Request $request, Donation $donation): Response
    {
        return $this->downloadReceipt($donation);
    }

    public function token(Request $request, Donation $donation, string $token): Response
    {
        if ($token !== $donation->receipt_token) {
            throw new NotFoundHttpException('Receipt not available for this donation.');
        }

        return $this->downloadReceipt($donation);
    }

    public function downloadForOrganization(Organization $organization, Donation $donation): Response
    {
        return $this->download($donation, $organization);
    }

    private function downloadReceipt(Donation $donation): Response
    {
        if ($donation->status->value !== 'succeeded') {
            throw new NotFoundHttpException('Receipt not available for this donation.');
        }

        $donation->loadMissing(['campaign.organization', 'donor']);

        $filename = config('app.name').'-'.$donation->campaign->organization->code.'-'.$donation->invoice_number.'.pdf';

        $pdf = Pdf::loadView('emails.donation-receipt-pdf', [
            'donation' => $donation,
        ]);

        return $pdf->download($filename);
    }

    private function download(Donation $donation, ?Organization $organization = null): Response
    {
        if ($donation->status->value !== 'succeeded') {
            throw new NotFoundHttpException('Receipt not available for this donation.');
        }

        if (! $this->canDownloadReceipt($donation, $organization)) {
            throw new NotFoundHttpException('Receipt not available for this donation.');
        }

        $donation->loadMissing(['campaign.organization', 'donor']);

        if ($organization !== null && $donation->campaign->organization_id !== $organization->getKey()) {
            throw new NotFoundHttpException('Receipt not available for this donation.');
        }

        $filename = config('app.name').'-'.$donation->campaign->organization->code.'-'.$donation->invoice_number.'.pdf';

        $pdf = Pdf::loadView('emails.donation-receipt-pdf', [
            'donation' => $donation,
        ]);

        return $pdf->download($filename);
    }

    private function canDownloadReceipt(Donation $donation, ?Organization $organization): bool
    {
        if (auth()->check()) {
            return true;
        }

        if (session('donor_id') !== $donation->donor_id) {
            return false;
        }

        return $organization === null || (string) session('organization_id') === (string) $organization->getKey();
    }
}
