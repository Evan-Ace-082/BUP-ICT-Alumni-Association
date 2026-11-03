<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_name',
        'batch_code',
        'session',
        'graduation_year',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
        ];
    }

    public function alumni(): HasMany
    {
        return $this->hasMany(Alumni::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Resolves the genuine ICT batch number (1, 2, ..., 50) if the
     * record genuinely represents an ICT batch, or null if unverified.
     */
    public function getBatchNumber(): ?int
    {
        if (! empty($this->batch_code)) {
            if (preg_match('/^B0*([1-9]\d*)$/i', trim($this->batch_code), $matches)) {
                $num = (int) $matches[1];
                if ($num >= 1 && $num <= 50) {
                    return $num;
                }
            }
            if (preg_match('/^ICT-?0*([1-9]\d*)$/i', trim($this->batch_code), $matches)) {
                $num = (int) $matches[1];
                if ($num >= 1 && $num <= 50) {
                    return $num;
                }
            }
        }

        if (! empty($this->batch_name)) {
            if (preg_match('/^Batch\s*0*([1-9]\d*)$/i', trim($this->batch_name), $matches)) {
                $num = (int) $matches[1];
                if ($num >= 1 && $num <= 50) {
                    return $num;
                }
            }
            if (preg_match('/^ICT[-\s]*0*([1-9]\d*)$/i', trim($this->batch_name), $matches)) {
                $num = (int) $matches[1];
                if ($num >= 1 && $num <= 50) {
                    return $num;
                }
            }
        }

        return null;
    }

    /**
     * Check if this batch record genuinely represents an ICT batch.
     */
    public function isIctBatch(): bool
    {
        return $this->getBatchNumber() !== null;
    }

    /**
     * Standardized public display name (e.g. "ICT-1", "ICT-2").
     */
    public function getIctNameAttribute(): string
    {
        $num = $this->getBatchNumber();

        return $num !== null ? "ICT-{$num}" : ($this->batch_name ?? '');
    }

    public function getNameAttribute(): string
    {
        return $this->ict_name;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->ict_name;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (is_numeric($value)) {
            return $this->where('id', $value)->first();
        }

        return $this->where('batch_code', $value)->first();
    }
}
