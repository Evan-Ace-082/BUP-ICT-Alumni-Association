<?php

namespace App\Policies;

use App\Models\Committee;
use App\Models\User;

class CommitteePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function view(User $user, Committee $committee): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function update(User $user, Committee $committee): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function delete(User $user, Committee $committee): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }
}
