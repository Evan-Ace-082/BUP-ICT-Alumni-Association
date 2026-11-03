<?php

namespace App\Policies;

use App\Models\JobListing;
use App\Models\User;

class JobListingPolicy
{
    /**
     * Only the alumni who posted it may edit/close it — admin/super-
     * admin may moderate (remove) any listing but that's a distinct
     * ability, see moderate().
     */
    public function update(User $user, JobListing $job): bool
    {
        return $user->id === $job->posted_by;
    }

    public function moderate(User $user, JobListing $job): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }
}
