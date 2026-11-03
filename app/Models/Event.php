<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    public const TYPES = ['Reunion', 'Seminar', 'Workshop', 'Career', 'Networking', 'Sports', 'Other'];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_CANCELLED, self::STATUS_COMPLETED];

    protected $fillable = [
        'title', 'description', 'event_type', 'event_date', 'start_time', 'end_time',
        'venue', 'location', 'registration_url', 'cover_image', 'status', 'created_by', 'batch_id',
    ];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Same visibility shape as Announcement::scopeVisibleToUser() —
     * general (no batch) events are visible to everyone; batch-scoped
     * events only to that batch's alumni/rep, or admin/super-admin.
     */
    public function scopeVisibleToUser($query, ?User $user)
    {
        if (! $user) {
            return $query->whereNull('batch_id');
        }

        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return $query;
        }

        $batchId = $user->hasRole('batch-representative')
            ? $user->assigned_batch_id
            : $user->alumni?->batch_id;

        return $query->where(function ($q) use ($batchId) {
            $q->whereNull('batch_id');

            if ($batchId) {
                $q->orWhere('batch_id', $batchId);
            }
        });
    }
}
