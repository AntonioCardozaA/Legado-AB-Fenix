<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisDeletionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'analysis_type',
        'analysis_model',
        'analysis_table',
        'deleted_record_id',
        'linea_id',
        'linea_nombre',
        'tipo_analisis',
        'deleted_at',
        'metadata',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }
}
