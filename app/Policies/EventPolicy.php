<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function create(User $user): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('batch-representative') && $user->assigned_batch_id !== null;
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        if (! $user->hasRole('batch-representative') || $user->assigned_batch_id === null) {
            return false;
        }

        return $event->batch_id !== null && $event->batch_id === $user->assigned_batch_id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
