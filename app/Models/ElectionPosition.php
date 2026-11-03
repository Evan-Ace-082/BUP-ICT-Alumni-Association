<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'name',
        'display_order',
        'tie_resolved_at',
        'tie_resolution_method',
        'tie_resolution_reason',
        'tie_resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'tie_resolved_at' => 'datetime',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(ElectionCandidate::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ElectionVote::class);
    }

    public function tieResolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tie_resolved_by');
    }

    public function isTieResolved(): bool
    {
        return ! is_null($this->tie_resolved_at);
    }

    public function getTieResolutionMethodLabelAttribute(): string
    {
        return match ($this->tie_resolution_method) {
            'committee_consensus' => 'Executive Committee Consensus',
            'lottery_draw' => 'Official Lottery / Random Draw',
            'academic_merit' => 'Academic / Seniority Merit',
            'runoff_outcome' => 'Runoff Election Outcome',
            'admin_discretion' => 'Election Commission Determination',
            default => ucfirst(str_replace('_', ' ', (string) ($this->tie_resolution_method ?? 'Admin Resolution'))),
        };
    }
}
