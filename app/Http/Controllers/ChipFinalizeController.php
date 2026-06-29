<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Chip\FinalizeDonation;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class ChipFinalizeController extends Controller
{
    public function __construct(private FinalizeDonation $finalizeDonation) {}

    public function store(Donation $donation): JsonResponse
    {
        try {
            $this->finalizeDonation->finalize($donation);
        } catch (RuntimeException $e) {
            return response()->json(['finalized' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['finalized' => false, 'message' => 'Unable to finalize donation.'], 500);
        }

        return response()->json(['finalized' => true]);
    }
}
