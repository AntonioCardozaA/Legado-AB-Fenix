<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceHistoryChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'source_type',
        'source_id',
        'chunk_index',
        'linea_id',
        'componente_id',
        'source_date',
        'title',
        'content',
        'searchable_text',
        'token_count',
        'metadata',
        'embedding',
        'embedding_model',
        'content_hash',
        'indexed_at',
    ];

    protected $casts = [
        'source_date' => 'datetime',
        'metadata' => 'array',
        'embedding' => 'array',
        'indexed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function sourceReference(): string
    {
        return trim(str_replace('_', ' ', $this->source_type)) . ' #' . $this->source_id;
    }
}
