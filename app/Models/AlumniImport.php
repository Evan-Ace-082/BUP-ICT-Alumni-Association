<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniImport extends Model
{
    protected $fillable = [
        'file_name',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'imported_by',
        'status',
        'imported_at',
        'error_report',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'error_report' => 'array',
        ];
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
