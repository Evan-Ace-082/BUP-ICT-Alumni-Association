<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function alumni(): HasOne
    {
        return $this->hasOne(Alumni::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function mentorProfile(): HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function mentorshipEligibilityOverride(): HasOne
    {
        return $this->hasOne(MentorshipEligibilityOverride::class);
    }

    public function sentMentorshipRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentee_id');
    }

    public function receivedMentorshipRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_id');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isAlumni(): bool
    {
        return $this->hasRole('alumni');
    }

    public function isApprovedMentor(): bool
    {
        return $this->mentorProfile?->status === MentorProfile::STATUS_APPROVED;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function assignedBatch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'assigned_batch_id');
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }
}
