<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;

class AlbumPolicy
{
    public function create(User $user): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('batch-representative') && $user->assigned_batch_id !== null;
    }

    /**
     * Also governs photo upload/delete within the album — the
     * controller always authorizes against the parent Album, never a
     * Photo in isolation, so a batch rep can never manage photos in
     * another batch's album regardless of the photo ID supplied.
     */
    public function update(User $user, Album $album): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        if (! $user->hasRole('batch-representative') || $user->assigned_batch_id === null) {
            return false;
        }

        return $album->batch_id !== null && $album->batch_id === $user->assigned_batch_id;
    }

    public function delete(User $user, Album $album): bool
    {
        return $this->update($user, $album);
    }

    public function publish(User $user, Album $album): bool
    {
        return $this->update($user, $album);
    }
}
