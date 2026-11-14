<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MentorProfile extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    public const AVAILABILITY_AVAILABLE = 'available';
    public const AVAILABILITY_LIMITED = 'limited';
    public const AVAILABILITY_FULLY_BOOKED = 'fully_booked';
    public const AVAILABILITY_TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';

    protected $fillable = [
        'user_id',
        'alumni_id',
        'status',
        'current_designation',
        'company',
        'industry',
        'country',
        'city',
        'years_of_experience',
        'linkedin_url',
        'bio',
        'expertise_areas',
        'topics_offered',
        'preferred_mentee_audience',
        'mentorship_mode',
        'preferred_language',
        'availability_schedule',
        'max_mentees',
        'mentorship_frequency',
        'personal_statement',
        'is_paused',
        'rejection_reason',
        'admin_notes',
        'approved_at',
        'approved_by',
        'suspended_at',
        'suspended_by',
    ];

    protected function casts(): array
    {
        return [
            'years_of_experience' => 'integer',
            'max_mentees' => 'integer',
            'is_paused' => 'boolean',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function suspender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(MentorshipCategory::class, 'mentor_profile_category', 'mentor_profile_id', 'category_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_profile_id');
    }

    public function activeRequests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_profile_id')
            ->where('status', MentorshipRequest::STATUS_ACCEPTED);
    }

    public function getActiveMenteesCountAttribute(): int
    {
        return $this->activeRequests()->count();
    }

    public function getRemainingSlotsAttribute(): int
    {
        return max(0, $this->max_mentees - $this->active_mentees_count);
    }

    public function getAvailabilityStatusAttribute(): string
    {
        if ($this->status !== self::STATUS_APPROVED || $this->is_paused) {
            return self::AVAILABILITY_TEMPORARILY_UNAVAILABLE;
        }

        $remaining = $this->remaining_slots;

        if ($remaining <= 0) {
            return self::AVAILABILITY_FULLY_BOOKED;
        }

        if ($remaining === 1) {
            return self::AVAILABILITY_LIMITED;
        }

        return self::AVAILABILITY_AVAILABLE;
    }

    public function isAcceptingRequests(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && ! $this->is_paused
            && $this->remaining_slots > 0;
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_APPROVED)->where('is_paused', false);
    }
}
