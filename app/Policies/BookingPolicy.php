<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPanelAccess;

class BookingPolicy
{
    use ChecksAdminPanelAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessPanel($user);
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->canAccessPanel($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->canAccessPanel($user);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }
}
