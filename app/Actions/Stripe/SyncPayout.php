<?php

declare(strict_types=1);

namespace App\Actions\Stripe;

use App\Models\Organization;
use App\Models\Payout;
use Stripe\Account;
use Stripe\Stripe;
use Throwable;

class SyncPayout
{
    /**
     * @param  array<string, mixed>  $stripePayout
     */
    public function sync(Organization $organization, array $stripePayout): Payout
    {
        $destination = $this->resolveDestination($organization, $stripePayout['destination'] ?? null);

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
                'bank_name' => $destination['bank_name'] ?? $destination['brand'] ?? null,
                'bank_account_last4' => $destination['last4'] ?? null,
                'failure_code' => $stripePayout['failure_code'] ?? null,
                'failure_message' => $stripePayout['failure_message'] ?? null,
                'metadata' => $stripePayout,
            ]
        );

        return $payout;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveDestination(Organization $organization, mixed $destination): ?array
    {
        if (is_array($destination)) {
            return $destination;
        }

        if (! is_string($destination) || blank($organization->stripe_account_id)) {
            return null;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            return Account::retrieveExternalAccount($organization->stripe_account_id, $destination, [], [])->toArray();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
