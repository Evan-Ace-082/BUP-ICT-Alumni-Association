<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'title', 'description', 'batch_id', 'event_id', 'cover_photo', 'created_by', 'status',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Same visibility shape as Announcement/Event — a batch-less
     * album is visible to everyone; a batch-scoped album only to
     * that batch's alumni/rep, or admin/super-admin.
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
