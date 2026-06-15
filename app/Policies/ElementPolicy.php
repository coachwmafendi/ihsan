<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Element;
use App\Models\User;

class ElementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function view(User $user, Element $element): bool
    {
        return $element->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::NgoAdmin;
    }

    public function update(User $user, Element $element): bool
    {
        return $element->organization_id === $user->organization_id;
    }

    public function delete(User $user, Element $element): bool
    {
        return $element->organization_id === $user->organization_id;
    }

    public function archive(User $user, Element $element): bool
    {
        return $element->organization_id === $user->organization_id;
    }
}
