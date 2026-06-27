<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Chip\ConfirmPurchase;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChipCheckoutController extends Controller
{
    public function callback(Request $request): View
    {
        $donationPublicId = $request->query('donation');
        $status = $request->query('status');
        $returnTo = $request->query('return_to');

        return view('chip.callback', [
            'donationPublicId' => $donationPublicId,
            'status' => $status,
            'returnTo' => $returnTo,
        ]);
    }

    public function confirm(Donation $donation): JsonResponse
    {
        try {
            $succeeded = app(ConfirmPurchase::class)->handle($donation);

            return response()->json([
                'success' => $succeeded,
                'donation_id' => $donation->public_id,
                'status' => $donation->fresh()?->status?->value,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
