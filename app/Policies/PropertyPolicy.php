<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPanelAccess;

class PropertyPolicy
{
    use ChecksAdminPanelAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessPanel($user);
    }

    public function view(User $user, Property $property): bool
    {
        return $this->canAccessPanel($user);
    }

    public function create(User $user): bool
    {
        return $this->canAccessPanel($user);
    }

    public function update(User $user, Property $property): bool
    {
        return $this->canAccessPanel($user);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->isAdmin($user);
    }
}
