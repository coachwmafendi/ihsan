<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Support\Currency;

trait DonorPortalScoping
{
    private function scopeToOrg($query, Organization $organization)
    {
        return $query->whereIn(
            'campaign_id',
            Campaign::where('organization_id', $organization->getKey())->select('id')
        );
    }

    /** Everything the donor has given, in ringgit at each donation's own rate. */
    private function getTotalGiven(Donor $donor, Organization $organization): float
    {
        return (float) $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->sum(Donation::reportAmountColumn());
    }

    private function getCurrencyBreakdown(Donor $donor, Organization $organization): array
    {
        return $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw('currency, SUM(gross_amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->mapWithKeys(function ($total, $currency) {
                return [$currency => Currency::format($currency, (float) $total)];
            })
            ->toArray();
    }
}
