<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Election extends Model
{
    use HasFactory;

    public const TYPE_ONLINE = 'online';

    public const TYPE_PHYSICAL = 'physical';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_FINALIZED = 'finalized';

    public const SUGGESTED_POSITIONS = [
        'President',
        'Vice President',
        'General Secretary',
        'Joint Secretary',
        'Treasurer',
        'Organizing Secretary',
        'Executive Member',
        'Logistics',
        'Assistant General Secretary',
        'Assistant Joint Secretary',
    ];

    protected $fillable = [
        'title',
        'description',
        'type',
        'term',
        'start_time',
        'end_time',
        'created_by',
        'status',
        'finalized_at',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'finalized_at' => 'datetime',
            'is_archived' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(ElectionPosition::class)->orderBy('display_order')->orderBy('id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(ElectionCandidate::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ElectionVote::class);
    }

    public function committee(): HasOne
    {
        return $this->hasOne(Committee::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    public function isOnline(): bool
    {
        return $this->type === self::TYPE_ONLINE;
    }

    public function isPhysical(): bool
    {
        return $this->type === self::TYPE_PHYSICAL;
    }

    public function isOpenForVoting(): bool
    {
        if (! $this->isOnline() || ! $this->isOngoing()) {
            return false;
        }

        $now = Carbon::now();

        return $now->gte($this->start_time) && $now->lte($this->end_time);
    }

    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    public function canBeDeleted(): bool
    {
        return $this->votes()->count() === 0
            && ! $this->isFinalized()
            && ! $this->isOngoing()
            && ! $this->committee()->exists();
    }

    public function canBeArchived(): bool
    {
        return ! $this->isArchived() && ! $this->isOngoing();
    }
}
