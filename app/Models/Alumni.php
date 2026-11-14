<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alumni extends Model
{
    use HasFactory;

    protected $table = 'alumni';

    public const STATUS_UNCLAIMED = 'unclaimed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const VERIFICATION_STATUS_PENDING = 'pending';

    public const VERIFICATION_STATUS_VERIFIED = 'verified';

    public const VERIFICATION_STATUS_REJECTED = 'rejected';

    public const VERIFICATION_STATUSES = [
        self::VERIFICATION_STATUS_PENDING,
        self::VERIFICATION_STATUS_VERIFIED,
        self::VERIFICATION_STATUS_REJECTED,
    ];

    public const STATUSES = [
        self::STATUS_UNCLAIMED,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    public const JOB_STATUS_EMPLOYED = 'Employed';

    public const JOB_STATUS_UNEMPLOYED = 'Unemployed';

    public const JOB_STATUS_STUDENT = 'Student';

    public const JOB_STATUS_ENTREPRENEUR = 'Entrepreneur';

    public const JOB_STATUS_HIGHER_STUDIES = 'Higher Studies';

    public const JOB_STATUS_OTHER = 'Other';

    public const JOB_STATUSES = [
        self::JOB_STATUS_EMPLOYED,
        self::JOB_STATUS_UNEMPLOYED,
        self::JOB_STATUS_STUDENT,
        self::JOB_STATUS_ENTREPRENEUR,
        self::JOB_STATUS_HIGHER_STUDIES,
        self::JOB_STATUS_OTHER,
    ];

    protected $fillable = [
        'student_id',
        'full_name',
        'email',
        'batch_id',
        'session',
        'graduation_year',
        'profile_photo',
        'phone',
        'user_id',
        'status',
        'alumni_id',
        'verification_status',
        'qr_token',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'current_institution',
        'designation',
        'company',
        'higher_studies',
        'address',
        'linkedin_url',
        'facebook_url',
        'blood_group',
        'country',
        'city',
        'skills',
        'job_status',
        'mentorship',
    ];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'mentorship' => 'boolean',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentorProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function scopeUnclaimed($query)
    {
        return $query->where('status', self::STATUS_UNCLAIMED);
    }

    public static function likeOperator(?string $connection = null): string
    {
        return \Illuminate\Support\Facades\DB::connection($connection)->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('verification_status', self::VERIFICATION_STATUS_PENDING);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_STATUS_VERIFIED);
    }

    public function scopeVerificationRejected($query)
    {
        return $query->where('verification_status', self::VERIFICATION_STATUS_REJECTED);
    }

    public function scopeSearchNameOrStudentId($query, string $term)
    {
        $like = static::likeOperator($query->getConnection()->getName());
        return $query->where(function ($q) use ($term, $like) {
            $q->where('full_name', $like, "%{$term}%")
                ->orWhere('student_id', $like, "%{$term}%");
        });
    }

    public function scopeInBatch($query, int $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeGraduationYear($query, int $year)
    {
        return $query->where('graduation_year', $year);
    }

    public function scopeBloodGroup($query, string $bloodGroup)
    {
        return $query->where('blood_group', $bloodGroup);
    }

    public function scopeCompanyLike($query, string $term)
    {
        $like = static::likeOperator($query->getConnection()->getName());
        return $query->where('company', $like, "%{$term}%");
    }

    public function scopeCountryLike($query, string $term)
    {
        $like = static::likeOperator($query->getConnection()->getName());
        return $query->where('country', $like, "%{$term}%");
    }

    public function scopeCityLike($query, string $term)
    {
        $like = static::likeOperator($query->getConnection()->getName());
        return $query->where('city', $like, "%{$term}%");
    }

    public function scopeSkillLike($query, string $term)
    {
        $like = static::likeOperator($query->getConnection()->getName());
        return $query->where('skills', $like, "%{$term}%");
    }

    public function scopeJobStatus($query, string $jobStatus)
    {
        return $query->where('job_status', $jobStatus);
    }

    public function scopeMentorshipAvailable($query, bool $mentorship)
    {
        return $query->where('mentorship', $mentorship);
    }

    public function scopePublicDirectory($query)
    {
        return $query->whereNotIn('status', [self::STATUS_INACTIVE, self::STATUS_REJECTED]);
    }

    public function scopeAbroad($query)
    {
        return $query->whereNotNull('country')
            ->whereRaw("TRIM(country) != ''")
            ->whereRaw("LOWER(TRIM(country)) NOT IN ('bangladesh', 'bd', 'dhaka', 'bangladesh.')");
    }

    public function scopeEntrepreneurs($query)
    {
        $like = static::likeOperator($query->getConnection()->getName());
        return $query->where(function ($q) use ($like) {
            $q->where('job_status', self::JOB_STATUS_ENTREPRENEUR)
                ->orWhere('designation', $like, '%founder%')
                ->orWhere('designation', $like, '%co-founder%')
                ->orWhere('designation', $like, '%owner%')
                ->orWhere('designation', $like, '%proprietor%')
                ->orWhere('designation', $like, '%entrepreneur%')
                ->orWhere('designation', $like, '%ceo%');
        });
    }

    public function scopeHigherStudiesAndResearch($query)
    {
        $like = static::likeOperator($query->getConnection()->getName());
        return $query->where(function ($q) use ($like) {
            $q->whereIn('job_status', [self::JOB_STATUS_HIGHER_STUDIES, self::JOB_STATUS_STUDENT])
                ->orWhere(function ($sub) {
                    $sub->whereNotNull('higher_studies')->where('higher_studies', '!=', '');
                })
                ->orWhere('designation', $like, '%research%')
                ->orWhere('designation', $like, '%phd%')
                ->orWhere('designation', $like, '%fellow%')
                ->orWhere('designation', $like, '%postdoc%')
                ->orWhere('designation', $like, '%student%');
        });
    }

    public function scopeProfessionalsInBangladesh($query)
    {
        $notLike = static::likeOperator($query->getConnection()->getName()) === 'ilike' ? 'not ilike' : 'not like';
        return $query->where(function ($q) use ($notLike) {
            $q->where(function ($countryQ) {
                $countryQ->whereNull('country')
                    ->orWhere('country', '')
                    ->orWhereRaw("LOWER(TRIM(country)) IN ('bangladesh', 'bd')");
            })
            ->where(function ($workQ) {
                $workQ->where('job_status', self::JOB_STATUS_EMPLOYED)
                    ->orWhere(function ($cQ) {
                        $cQ->whereNotNull('company')->where('company', '!=', '');
                    })
                    ->orWhere(function ($dQ) {
                        $dQ->whereNotNull('designation')->where('designation', '!=', '');
                    });
            })
            ->where(function ($notEntQ) {
                $notEntQ->whereNull('job_status')
                    ->orWhere('job_status', '!=', self::JOB_STATUS_ENTREPRENEUR);
            })
            ->where(function ($notDesigQ) use ($notLike) {
                $notDesigQ->whereNull('designation')
                    ->orWhere(function ($sub) use ($notLike) {
                        $sub->where('designation', $notLike, '%founder%')
                            ->where('designation', $notLike, '%co-founder%')
                            ->where('designation', $notLike, '%owner%')
                            ->where('designation', $notLike, '%proprietor%')
                            ->where('designation', $notLike, '%ceo%')
                            ->where('designation', $notLike, '%research%')
                            ->where('designation', $notLike, '%phd%')
                            ->where('designation', $notLike, '%fellow%')
                            ->where('designation', $notLike, '%postdoc%');
                    });
            })
            ->where(function ($notHsQ) {
                $notHsQ->whereNull('job_status')
                    ->orWhereNotIn('job_status', [self::JOB_STATUS_HIGHER_STUDIES, self::JOB_STATUS_STUDENT]);
            })
            ->where(function ($noHsField) {
                $noHsField->whereNull('higher_studies')
                    ->orWhere('higher_studies', '');
            });
        });
    }

    public function isAbroad(): bool
    {
        if (blank($this->country)) {
            return false;
        }

        $norm = strtolower(trim($this->country));

        return ! in_array($norm, ['bangladesh', 'bd', 'dhaka', 'bangladesh.'], true);
    }

    public function isEntrepreneur(): bool
    {
        if ($this->job_status === self::JOB_STATUS_ENTREPRENEUR) {
            return true;
        }

        if (! blank($this->designation)) {
            $desig = strtolower($this->designation);
            foreach (['founder', 'co-founder', 'owner', 'proprietor', 'entrepreneur', 'ceo'] as $keyword) {
                if (str_contains($desig, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isHigherStudiesAndResearch(): bool
    {
        if (in_array($this->job_status, [self::JOB_STATUS_HIGHER_STUDIES, self::JOB_STATUS_STUDENT], true)) {
            return true;
        }

        if (! blank($this->higher_studies)) {
            return true;
        }

        if (! blank($this->designation)) {
            $desig = strtolower($this->designation);
            foreach (['research', 'phd', 'fellow', 'postdoc', 'student', 'scholar'] as $keyword) {
                if (str_contains($desig, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isProfessionalInBangladesh(): bool
    {
        if ($this->isAbroad()) {
            return false;
        }

        $hasWork = $this->job_status === self::JOB_STATUS_EMPLOYED
            || ! blank($this->company)
            || ! blank($this->designation);

        if (! $hasWork) {
            return false;
        }

        if ($this->isEntrepreneur() || $this->isHigherStudiesAndResearch()) {
            return false;
        }

        return true;
    }

    public function getCategories(): array
    {
        $categories = [];

        if ($this->isAbroad()) {
            $categories[] = 'Abroad';
        }
        if ($this->isProfessionalInBangladesh()) {
            $categories[] = 'Bangladesh Professional';
        }
        if ($this->isEntrepreneur()) {
            $categories[] = 'Entrepreneur';
        }
        if ($this->isHigherStudiesAndResearch()) {
            $categories[] = 'Higher Studies & Research';
        }

        return $categories;
    }

    public function isVerified(): bool
    {
        if ($this->verification_status === self::VERIFICATION_STATUS_VERIFIED) {
            return true;
        }

        if ($this->verification_status === self::VERIFICATION_STATUS_PENDING
            || $this->verification_status === self::VERIFICATION_STATUS_REJECTED) {
            return false;
        }

        // Fallback for legacy existing approved records prior to backfill
        return $this->user_id !== null && in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ACTIVE], true);
    }

    public function isPendingVerification(): bool
    {
        return $this->verification_status === self::VERIFICATION_STATUS_PENDING;
    }

    public function isVerificationRejected(): bool
    {
        return $this->verification_status === self::VERIFICATION_STATUS_REJECTED;
    }

    public function canLogIn(): bool
    {
        return in_array(
            $this->status,
            [self::STATUS_APPROVED, self::STATUS_ACTIVE],
            true
        );
    }

    public static function maskStudentId(?string $id): string
    {
        if (! $id) {
            return '—';
        }

        $length = strlen($id);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        if ($length <= 6) {
            return substr($id, 0, 2).str_repeat('*', $length - 4).substr($id, -2);
        }

        $prefixLen = (int) floor($length / 3);
        $suffixLen = (int) floor($length / 3);
        $maskLen = $length - $prefixLen - $suffixLen;

        return substr($id, 0, $prefixLen).str_repeat('*', $maskLen).substr($id, -$suffixLen);
    }

    public static function generateNextAlumniId(): string
    {
        $maxNum = 0;
        $ids = static::whereNotNull('alumni_id')
            ->where('alumni_id', 'like', 'BUPICT-AL-%')
            ->pluck('alumni_id');

        foreach ($ids as $val) {
            if (preg_match('/BUPICT-AL-(\d+)/', (string) $val, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $candidate = sprintf('BUPICT-AL-%06d', $maxNum + 1);

        while (static::where('alumni_id', $candidate)->exists()) {
            $maxNum++;
            $candidate = sprintf('BUPICT-AL-%06d', $maxNum + 1);
        }

        return $candidate;
    }

    public static function generateQrToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::where('qr_token', $token)->exists());

        return $token;
    }

    public function qrVerificationUrl(): ?string
    {
        if (! $this->qr_token) {
            return null;
        }

        $baseUrl = rtrim(env('ALUMNI_VERIFY_URL') ?: config('app.url') ?: url('/'), '/');

        return "{$baseUrl}/verify-alumni/{$this->qr_token}";
    }

    public function qrCodeSvg(int $scale = 5): ?string
    {
        $url = $this->qrVerificationUrl();
        if (! $url) {
            return null;
        }

        return app(\App\Services\QrCodeService::class)->generateSvg($url, $scale);
    }

    public function qrCodeDataUri(int $scale = 5): ?string
    {
        $url = $this->qrVerificationUrl();
        if (! $url) {
            return null;
        }

        return app(\App\Services\QrCodeService::class)->generateDataUri($url, $scale);
    }

    public function getNameAttribute(): string
    {
        return $this->full_name ?? '';
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['full_name'] = $value;
    }

    public function getProfilePhotoPathAttribute(): ?string
    {
        return $this->profile_photo;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        if (str_starts_with($this->profile_photo, 'http://') || str_starts_with($this->profile_photo, 'https://')) {
            return $this->profile_photo;
        }

        return asset('storage/'.$this->profile_photo);
    }
}
