<?php

namespace App\Policies;

use App\Models\MentorshipRequest;
use App\Models\User;

class MentorshipRequestPolicy
{
    public function view(User $user, MentorshipRequest $request): bool
    {
        return $user->id === $request->mentor_id
            || $user->id === $request->mentee_id
            || $user->hasRole('admin')
            || $user->hasRole('super-admin');
    }

    /**
     * Only the mentor being requested may accept/reject/decline.
     */
    public function respond(User $user, MentorshipRequest $request): bool
    {
        return $user->id === $request->mentor_id && $request->status === MentorshipRequest::STATUS_PENDING;
    }

    /**
     * Only the mentee who sent it may cancel.
     */
    public function cancel(User $user, MentorshipRequest $request): bool
    {
        return $user->id === $request->mentee_id && $request->status === MentorshipRequest::STATUS_PENDING;
    }

    /**
     * Either the mentor or mentee can mark an active mentorship as completed.
     */
    public function complete(User $user, MentorshipRequest $request): bool
    {
        return ($user->id === $request->mentor_id || $user->id === $request->mentee_id)
            && $request->status === MentorshipRequest::STATUS_ACCEPTED;
    }
}
