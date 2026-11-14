<?php

namespace App\Policies;

use App\Models\MentorProfile;
use App\Models\User;

class MentorProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MentorProfile $profile): bool
    {
        return $profile->status === MentorProfile::STATUS_APPROVED
            || $user->id === $profile->user_id
            || $user->hasRole('admin')
            || $user->hasRole('super-admin');
    }

    public function update(User $user, MentorProfile $profile): bool
    {
        return $user->id === $profile->user_id
            || $user->hasRole('admin')
            || $user->hasRole('super-admin');
    }

    public function manage(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }
}
