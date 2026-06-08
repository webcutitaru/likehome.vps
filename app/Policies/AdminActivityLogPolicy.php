<?php

namespace App\Policies;

use App\Models\AdminActivityLog;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPanelAccess;

class AdminActivityLogPolicy
{
    use ChecksAdminPanelAccess;

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, AdminActivityLog $log): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AdminActivityLog $log): bool
    {
        return false;
    }

    public function delete(User $user, AdminActivityLog $log): bool
    {
        return false;
    }
}
