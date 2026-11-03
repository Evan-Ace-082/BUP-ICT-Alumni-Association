<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    public const TARGET_ALL = 'all';

    public const TARGET_BATCH = 'batch';

    public const TARGET_TYPES = [self::TARGET_ALL, self::TARGET_BATCH];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED];

    protected $fillable = [
        'title',
        'body',
        'target_type',
        'batch_id',
        'created_by',
        'is_public',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'published_at' => 'datetime',
        ];
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

    public function scopeGeneral($query)
    {
        return $query->where('target_type', self::TARGET_ALL);
    }

    public function scopeForBatch($query, int $batchId)
    {
        return $query->where('target_type', self::TARGET_BATCH)->where('batch_id', $batchId);
    }

    /**
     * General announcements are always guest-visible by definition.
     * A batch-specific announcement is guest-visible only when
     * explicitly marked is_public — e.g. a batch reunion event an
     * admin wants advertised outside the platform. Does NOT filter by
     * status; combine with published() where that matters.
     */
    public function scopeVisibleToGuest($query)
    {
        return $query->where(function ($q) {
            $q->where('target_type', self::TARGET_ALL)->orWhere('is_public', true);
        });
    }

    /**
     * The single source of truth for "can this user see this
     * announcement (or these announcements)". Used by both the public
     * index/show and the dashboard listing, so the rule is defined
     * exactly once. Does NOT filter by status; combine with
     * published() separately, since admins need to see drafts too in
     * their own management views (which don't use this scope).
     *
     * - Guest (null $user): general or explicitly public.
     * - Admin/super-admin: everything.
     * - Batch representative: general, explicitly public, or their
     *   own assigned batch.
     * - Alumni: general, explicitly public, or their own linked
     *   batch.
     */
    public function scopeVisibleToUser($query, ?User $user)
    {
        if (! $user) {
            return $query->visibleToGuest();
        }

        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return $query;
        }

        $batchId = $user->hasRole('batch-representative')
            ? $user->assigned_batch_id
            : $user->alumni?->batch_id;

        return $query->where(function ($q) use ($batchId) {
            $q->where('target_type', self::TARGET_ALL)->orWhere('is_public', true);

            if ($batchId) {
                $q->orWhere('batch_id', $batchId);
            }
        });
    }
}
