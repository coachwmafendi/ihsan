<?php

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;

trait DonorPortalScoping
{
    private function scopeToOrg($query, Organization $organization)
    {
        return $query->whereIn(
            'campaign_id',
            Campaign::where('organization_id', $organization->getKey())->select('id')
        );
    }

    private function getTotalGiven(Donor $donor, Organization $organization): float
    {
        return (float) $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->sum(DB::raw('COALESCE(base_amount, gross_amount)'));
    }

    private function getCurrencyBreakdown(Donor $donor, Organization $organization): array
    {
        return $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw('currency, SUM(gross_amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->mapWithKeys(function ($total, $currency) {
                return [$currency => Currency::symbol($currency).' '.number_format((float) $total, 2)];
            })
            ->toArray();
    }
}
