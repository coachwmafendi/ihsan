<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $campaign->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $campaign->organization_id === $user->organization_id;
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $campaign->organization_id === $user->organization_id;
    }

    public function archive(User $user, Campaign $campaign): bool
    {
        return $campaign->organization_id === $user->organization_id;
    }
}
