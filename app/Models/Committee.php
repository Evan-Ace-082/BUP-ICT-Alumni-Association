<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Committee extends Model
{
    use HasFactory;

    protected $fillable = [
        'term',
        'is_current',
        'election_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommitteeMember::class)->orderBy('display_order')->orderBy('id');
    }

    public function activate(): void
    {
        DB::transaction(function () {
            self::where('id', '!=', $this->id)->update(['is_current' => false]);
            $this->update(['is_current' => true]);
        });
    }
}
