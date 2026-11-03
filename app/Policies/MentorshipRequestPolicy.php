<?php

namespace App\Policies;

use App\Models\MentorshipRequest;
use App\Models\User;

class MentorshipRequestPolicy
{
    public function view(User $user, MentorshipRequest $request): bool
    {
        return $user->id === $request->mentor_id || $user->id === $request->mentee_id;
    }

    /**
     * Only the mentor being requested may accept/reject — never the
     * mentee, never an unrelated user.
     */
    public function respond(User $user, MentorshipRequest $request): bool
    {
        return $user->id === $request->mentor_id && $request->status === MentorshipRequest::STATUS_PENDING;
    }

    /**
     * Only the mentee who sent it may cancel — never the mentor,
     * never an unrelated user.
     */
    public function cancel(User $user, MentorshipRequest $request): bool
    {
        return $user->id === $request->mentee_id && $request->status === MentorshipRequest::STATUS_PENDING;
    }
}
