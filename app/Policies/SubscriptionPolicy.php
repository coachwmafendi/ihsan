<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $subscription->campaign->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $subscription->campaign->organization_id === $user->organization_id;
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $subscription->campaign->organization_id === $user->organization_id;
    }
}
