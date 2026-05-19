<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Donor;
use App\Models\User;

class DonorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function view(User $user, Donor $donor): bool
    {
        return $donor->donations()
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $user->organization_id))
            ->exists()
            || $donor->subscriptions()
                ->whereHas('campaign', fn ($q) => $q->where('organization_id', $user->organization_id))
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function update(User $user, Donor $donor): bool
    {
        return $this->view($user, $donor);
    }

    public function delete(User $user, Donor $donor): bool
    {
        return $this->view($user, $donor);
    }
}
