<?php

namespace App\Policies;

use App\Models\DiscountCoupon;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPanelAccess;

class DiscountCouponPolicy
{
    use ChecksAdminPanelAccess;

    public function viewAny(User $user): bool
    {
        return $this->canAccessPanel($user);
    }

    public function view(User $user, DiscountCoupon $coupon): bool
    {
        return $this->canAccessPanel($user);
    }

    public function create(User $user): bool
    {
        return $this->canAccessPanel($user);
    }

    public function update(User $user, DiscountCoupon $coupon): bool
    {
        return $this->canAccessPanel($user);
    }

    public function delete(User $user, DiscountCoupon $coupon): bool
    {
        return $this->canAccessPanel($user);
    }
}
