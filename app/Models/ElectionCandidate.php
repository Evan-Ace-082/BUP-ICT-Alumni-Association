<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElectionCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'election_position_id',
        'alumni_id',
        'statement',
        'photo_path',
        'is_winner',
    ];

    protected function casts(): array
    {
        return [
            'is_winner' => 'boolean',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(ElectionPosition::class, 'election_position_id');
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ElectionVote::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo_path) {
            return \Illuminate\Support\Facades\Storage::url($this->photo_path);
        }

        if ($this->alumni?->profile_photo) {
            return \Illuminate\Support\Facades\Storage::url($this->alumni->profile_photo);
        }

        return null;
    }
}
