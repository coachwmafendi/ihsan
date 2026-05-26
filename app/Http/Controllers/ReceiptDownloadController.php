<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReceiptDownloadController extends Controller
{
    public function __invoke(Donation $donation): Response
    {
        if ($donation->status->value !== 'succeeded') {
            throw new NotFoundHttpException('Receipt not available for this donation.');
        }

        if (! $this->canDownloadReceipt($donation)) {
            throw new NotFoundHttpException('Receipt not available for this donation.');
        }

        $donation->loadMissing(['campaign.organization', 'donor']);

        $filename = config('app.name').'-'.$donation->campaign->organization->code.'-'.$donation->invoice_number.'.pdf';

        $pdf = Pdf::loadView('emails.donation-receipt-pdf', [
            'donation' => $donation,
        ]);

        return $pdf->download($filename);
    }

    private function canDownloadReceipt(Donation $donation): bool
    {
        return auth()->check() || session('donor_id') === $donation->donor_id;
    }
}
