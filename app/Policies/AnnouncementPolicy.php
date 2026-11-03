<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

/**
 * Deliberately separate from AlumniPolicy. Announcement visibility is
 * batch-specific; alumni profile visibility is NOT and must stay
 * global (see Phase 4). Never merge these two rule sets.
 */
class AnnouncementPolicy
{
    /**
     * Whether the user is allowed to view this specific announcement.
     * Guests are handled separately in the controller (a Policy always
     * needs a User instance) using Announcement::scopeVisibleToGuest().
     */
    public function view(User $user, Announcement $announcement): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        if ($announcement->target_type === Announcement::TARGET_ALL || $announcement->is_public) {
            return true;
        }

        if ($user->hasRole('batch-representative')) {
            return $user->assigned_batch_id !== null
                && $user->assigned_batch_id === $announcement->batch_id;
        }

        $alumniBatchId = $user->alumni?->batch_id;

        return $alumniBatchId !== null && $alumniBatchId === $announcement->batch_id;
    }

    /**
     * Whether the user can reach the "create announcement" form at
     * all. Admin/super-admin always can. A batch representative can,
     * as long as they have an assigned batch — but this does NOT by
     * itself authorize which batch they target; the controller always
     * forces batch_id from Auth::user()->assigned_batch_id and never
     * reads it from the request, so there is nothing here to check
     * against a submitted batch_id.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('batch-representative') && $user->assigned_batch_id !== null;
    }

    /**
     * Whether the user can edit/publish/unpublish this specific,
     * already-persisted announcement. A batch representative may only
     * act on a batch-targeted announcement that belongs to their own
     * assigned batch — never a general ('all') announcement, and never
     * another batch's, regardless of what the request claims.
     */
    public function update(User $user, Announcement $announcement): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        if (! $user->hasRole('batch-representative') || $user->assigned_batch_id === null) {
            return false;
        }

        return $announcement->target_type === Announcement::TARGET_BATCH
            && $announcement->batch_id === $user->assigned_batch_id;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    public function publish(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }
}
