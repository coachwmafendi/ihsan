<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class AuditLogLogger
{
    /**
     * Create a manual audit log entry.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function log(Model $subject, string $event, string $description, array $properties = [], ?User $causer = null): Activity
    {
        /** @var Activity $activity */
        $activity = activity()
            ->performedOn($subject)
            ->event($event)
            ->withProperties($properties)
            ->log($description);

        if ($causer !== null) {
            $activity->causer()->associate($causer);
            $activity->save();
        }

        return $activity;
    }

    public static function stripeConnected(Organization $organization, ?User $user, string $accountId): Activity
    {
        return self::log(
            $organization,
            'stripe_connected',
            "Stripe Connect account linked: {$accountId}",
            ['stripe_account_id' => $accountId],
            $user,
        );
    }

    public static function stripeDisconnected(Organization $organization, ?User $user): Activity
    {
        return self::log(
            $organization,
            'stripe_disconnected',
            'Stripe Connect account disconnected',
            [],
            $user,
        );
    }

    public static function stripeOnboardingCompleted(Organization $organization, ?User $user, string $accountId): Activity
    {
        return self::log(
            $organization,
            'stripe_onboarding_completed',
            "Stripe Connect onboarding completed: {$accountId}",
            ['stripe_account_id' => $accountId],
            $user,
        );
    }
}
