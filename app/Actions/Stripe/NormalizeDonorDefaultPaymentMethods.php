<?php

namespace App\Actions\Stripe;

use App\Models\DonorPaymentMethod;

class NormalizeDonorDefaultPaymentMethods
{
    /**
     * Collapse donors holding several default cards down to their newest one.
     *
     * Two writers used to flag a card as default without clearing the siblings,
     * so old records carry more than one. A donor with no default is left as is.
     *
     * @return int the number of rows cleared
     */
    public function run(): int
    {
        $donorIds = DonorPaymentMethod::query()
            ->where('is_default', true)
            ->groupBy('donor_id')
            ->havingRaw('count(*) > 1')
            ->pluck('donor_id');

        $cleared = 0;

        foreach ($donorIds as $donorId) {
            $keepId = DonorPaymentMethod::query()
                ->where('donor_id', $donorId)
                ->where('is_default', true)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->value('id');

            $cleared += DonorPaymentMethod::query()
                ->where('donor_id', $donorId)
                ->where('is_default', true)
                ->whereKeyNot($keepId)
                ->update(['is_default' => false]);
        }

        return $cleared;
    }
}
