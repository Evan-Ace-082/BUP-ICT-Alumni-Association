<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorshipRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'mentor_id',
        'mentee_id',
        'mentor_profile_id',
        'category_id',
        'specific_topic',
        'reason',
        'career_goals',
        'preferred_mode',
        'preferred_schedule',
        'message',
        'status',
        'decline_reason',
        'responded_at',
        'completed_at',
        'completed_by',
        'feedback',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'completed_at' => 'datetime',
            'rating' => 'integer',
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    public function mentorProfile(): BelongsTo
    {
        return $this->belongsTo(MentorProfile::class, 'mentor_profile_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MentorshipCategory::class, 'category_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('mentor_id', $userId)->orWhere('mentee_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
