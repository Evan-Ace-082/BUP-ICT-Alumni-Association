<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_GRADUATED = 'graduated';
    public const STATUS_INACTIVE = 'inactive';

    public const VERIFICATION_STATUS_PENDING = 'pending';
    public const VERIFICATION_STATUS_VERIFIED = 'verified';
    public const VERIFICATION_STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'student_id',
        'full_name',
        'email',
        'phone',
        'batch_id',
        'department',
        'session',
        'enrollment_year',
        'expected_graduation_year',
        'status',
        'verification_status',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_year' => 'integer',
            'expected_graduation_year' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_STATUS_VERIFIED;
    }

    public function isPending(): bool
    {
        return $this->verification_status === self::VERIFICATION_STATUS_PENDING;
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_STATUS_VERIFIED);
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', self::VERIFICATION_STATUS_PENDING);
    }
}
