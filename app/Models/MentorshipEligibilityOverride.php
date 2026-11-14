<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorshipEligibilityOverride extends Model
{
    use HasFactory;

    public const OVERRIDE_AUTO = 'auto';
    public const OVERRIDE_FORCE_MENTEE = 'force_mentee';
    public const OVERRIDE_FORCE_MENTOR = 'force_mentor';
    public const OVERRIDE_INELIGIBLE = 'ineligible';

    protected $fillable = [
        'user_id',
        'override_type',
        'reason',
        'admin_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
