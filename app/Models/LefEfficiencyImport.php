<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LefEfficiencyImport extends Model
{
    use HasFactory;

    protected $table = 'lef_efficiency_imports';

    protected $fillable = [
        'source_filename',
        'observations',
        'periods_count',
        'rows_count',
        'imported_by',
    ];

    protected $casts = [
        'periods_count' => 'integer',
        'rows_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(LefEfficiencyEntry::class, 'lef_efficiency_import_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
