<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Donation;
use App\Models\User;

class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function view(User $user, Donation $donation): bool
    {
        return $donation->campaign->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Donation $donation): bool
    {
        return false;
    }

    public function delete(User $user, Donation $donation): bool
    {
        return false;
    }
}
