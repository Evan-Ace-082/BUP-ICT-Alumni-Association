<?php

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;

class AchievementPolicy
{
    public function view(User $user, Achievement $achievement): bool
    {
        if ($achievement->status === Achievement::STATUS_APPROVED) {
            return true;
        }

        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        return $user->alumni && $user->alumni->id === $achievement->alumni_id;
    }

    /**
     * Only the owning alumni may edit their OWN, still-PENDING
     * submission. Once approved/rejected it's locked from
     * self-editing (admin can still edit/moderate).
     */
    public function update(User $user, Achievement $achievement): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        return $user->alumni
            && $user->alumni->id === $achievement->alumni_id
            && $achievement->status === Achievement::STATUS_PENDING;
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        return $this->update($user, $achievement);
    }

    /**
     * Nobody may approve/reject their own submission — admin/super-
     * admin only, and never the owning alumni even if they also hold
     * an admin role on a different account (moot here since it's
     * always a different account by definition, but stated for
     * clarity).
     */
    public function moderate(User $user, Achievement $achievement): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }
}
