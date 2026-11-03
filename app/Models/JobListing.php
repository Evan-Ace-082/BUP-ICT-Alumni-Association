<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobListing extends Model
{
    use HasFactory;

    protected $table = 'job_listings';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CLOSED = 'closed';

    public const EMPLOYMENT_TYPES = ['Full Time', 'Part Time', 'Internship', 'Contract', 'Remote', 'Other'];

    protected $fillable = [
        'posted_by', 'title', 'company', 'description', 'employment_type',
        'location', 'country', 'salary', 'application_url', 'application_email',
        'deadline', 'status',
    ];

    protected function casts(): array
    {
        return ['deadline' => 'date'];
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
