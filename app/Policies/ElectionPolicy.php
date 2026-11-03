<?php

namespace App\Policies;

use App\Models\Election;
use App\Models\User;

class ElectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function view(User $user, Election $election): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function update(User $user, Election $election): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function delete(User $user, Election $election): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function manage(User $user, Election $election): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function finalize(User $user, Election $election): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }
}
