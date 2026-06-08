<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksAdminPanelAccess
{
    protected function canAccessPanel(User $user): bool
    {
        return $user->status === 'active'
            && in_array($user->role, ['admin', 'manager'], true);
    }

    protected function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }
}
