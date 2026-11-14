<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorshipSetting extends Model
{
    public const MODE_MANUAL = 'manual';
    public const MODE_AUTOMATIC = 'automatic';

    protected $fillable = [
        'cohort_mode',
        'latest_graduation_year',
        'mentorship_enabled',
        'max_active_requests_per_mentee',
    ];

    protected function casts(): array
    {
        return [
            'latest_graduation_year' => 'integer',
            'mentorship_enabled' => 'boolean',
            'max_active_requests_per_mentee' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'cohort_mode' => self::MODE_MANUAL,
            'latest_graduation_year' => 2024,
            'mentorship_enabled' => true,
            'max_active_requests_per_mentee' => 3,
        ]);
    }
}
