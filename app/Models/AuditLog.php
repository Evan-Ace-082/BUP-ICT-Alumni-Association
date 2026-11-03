<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    // Phase 6.2 admin management actions
    public const ACTION_ADMIN_CREATED = 'admin_created';

    public const ACTION_ADMIN_SUSPENDED = 'admin_suspended';

    public const ACTION_ADMIN_RESTORED = 'admin_restored';

    public const ACTION_ADMIN_ROLE_REMOVED = 'admin_role_removed';

    // Phase 7 Election & Committee actions
    public const ACTION_ELECTION_CREATED = 'election_created';

    public const ACTION_ELECTION_UPDATED = 'election_updated';

    public const ACTION_ELECTION_OPENED = 'election_opened';

    public const ACTION_ELECTION_CLOSED = 'election_closed';

    public const ACTION_ELECTION_POSITION_ADDED = 'election_position_added';

    public const ACTION_ELECTION_POSITION_REMOVED = 'election_position_removed';

    public const ACTION_ELECTION_CANDIDATE_ADDED = 'election_candidate_added';

    public const ACTION_ELECTION_CANDIDATE_UPDATED = 'election_candidate_updated';

    public const ACTION_ELECTION_CANDIDATE_REMOVED = 'election_candidate_removed';

    public const ACTION_PHYSICAL_WINNER_SELECTED = 'physical_winner_selected';

    public const ACTION_ELECTION_TIE_RESOLVED = 'election_tie_resolved';

    public const ACTION_ELECTION_FINALIZED = 'election_finalized';

    public const ACTION_ELECTION_DELETED = 'election_deleted';

    public const ACTION_ELECTION_ARCHIVED = 'election_archived';

    public const ACTION_ELECTION_UNARCHIVED = 'election_unarchived';

    public const ACTION_COMMITTEE_CREATED = 'committee_created';

    public const ACTION_COMMITTEE_ACTIVATED = 'committee_activated';

    public const ACTION_COMMITTEE_MEMBER_ADDED = 'committee_member_added';

    public const ACTION_COMMITTEE_MEMBER_UPDATED = 'committee_member_updated';

    public const ACTION_COMMITTEE_MEMBER_REMOVED = 'committee_member_removed';

    protected $fillable = [
        'actor_id',
        'target_type',
        'target_id',
        'action',
        'details',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }
}
