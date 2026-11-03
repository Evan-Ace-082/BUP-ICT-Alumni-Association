<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];

    public const TYPES = [
        'Career Achievement', 'Academic Achievement', 'Award', 'Entrepreneurship',
        'Publication', 'Research', 'International Achievement', 'Community Contribution', 'Other',
    ];

    protected $fillable = [
        'alumni_id', 'achievement_category_id', 'title', 'description', 'achievement_type', 'achievement_date',
        'organization', 'image', 'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['achievement_date' => 'date', 'approved_at' => 'datetime'];
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AchievementCategory::class, 'achievement_category_id');
    }

    public function achievementCategory(): BelongsTo
    {
        return $this->belongsTo(AchievementCategory::class, 'achievement_category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
