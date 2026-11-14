<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MentorshipCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(MentorProfile::class, 'mentor_profile_category', 'category_id', 'mentor_profile_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
