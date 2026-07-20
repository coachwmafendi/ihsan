<?php

declare(strict_types=1);

namespace App\Actions\Stripe;

use App\Models\Organization;
use App\Models\Payout;

class SyncPayout
{
    /**
     * @param  array<string, mixed>  $stripePayout
     */
    public function sync(Organization $organization, array $stripePayout): Payout
    {
        $payout = Payout::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'stripe_payout_id' => $stripePayout['id'],
            ],
            [
                'amount' => (int) $stripePayout['amount'],
                'currency' => $stripePayout['currency'],
                'status' => $stripePayout['status'],
                'arrival_date' => isset($stripePayout['arrival_date']) ? now()->parse($stripePayout['arrival_date'])->toDateString() : now()->toDateString(),
                'paid_at' => ($stripePayout['status'] ?? null) === 'paid' ? now()->toDateString() : null,
                'bank_name' => $stripePayout['destination']['bank_name'] ?? null,
                'bank_account_last4' => $stripePayout['destination']['last4'] ?? null,
                'failure_code' => $stripePayout['failure_code'] ?? null,
                'failure_message' => $stripePayout['failure_message'] ?? null,
                'metadata' => $stripePayout,
            ]
        );

        return $payout;
    }
}
