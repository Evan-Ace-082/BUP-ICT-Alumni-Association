<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'committee_id',
        'alumni_id',
        'position_name',
        'display_order',
        'photo',
    ];

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    /**
     * Resolves member photo URL.
     * Prefers committee-specific photo if uploaded, otherwise falls back to alumni profile_photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://'))
                ? $this->photo
                : asset('storage/'.$this->photo);
        }

        if ($this->alumni && $this->alumni->profile_photo) {
            return (str_starts_with($this->alumni->profile_photo, 'http://') || str_starts_with($this->alumni->profile_photo, 'https://'))
                ? $this->alumni->profile_photo
                : asset('storage/'.$this->alumni->profile_photo);
        }

        return null;
    }
}
