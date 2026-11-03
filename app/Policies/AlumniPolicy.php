<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;

class AlumniPolicy
{
    /**
     * An authenticated alumni can view their own profile.
     * Admins and super-admins can view any alumni profile.
     * A batch representative can view only alumni within their own
     * assigned batch (Phase 3 Step 2).
     */
    public function view(User $user, Alumni $alumni): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        if ($user->hasRole('batch-representative')) {
            return $user->assigned_batch_id !== null
                && $user->assigned_batch_id === $alumni->batch_id;
        }

        return $alumni->user_id === $user->id;
    }

    /**
     * An authenticated alumni can update only their own profile.
     * Admins and super-admins can update any alumni profile.
     * A batch representative can update only alumni within their own
     * assigned batch — never another batch's data (Phase 3 Step 2).
     */
    public function update(User $user, Alumni $alumni): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        if ($user->hasRole('batch-representative')) {
            return $user->assigned_batch_id !== null
                && $user->assigned_batch_id === $alumni->batch_id;
        }

        return $alumni->user_id === $user->id;
    }
}
