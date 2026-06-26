<?php

namespace App\Actions\Stripe;

use App\Models\Subscription;

class ChangeRecurringAmount
{
    public function change(Subscription $subscription, float $newAmount): void
    {
        $subscription->update([
            'amount' => $newAmount,
        ]);
    }
}
