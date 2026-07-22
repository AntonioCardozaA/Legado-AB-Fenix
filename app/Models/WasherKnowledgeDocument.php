<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasherKnowledgeDocument extends Model
{
    use HasFactory;

    protected $table = 'washer_knowledge_documents';

    protected $fillable = [
        'linea_id',
        'componente_id',
        'title',
        'document_type',
        'version',
        'effective_at',
        'lifecycle_status',
        'storage_disk',
        'storage_path',
        'original_filename',
        'mime_type',
        'uploaded_by',
        'uploaded_at',
        'metadata',
        'indexing_status',
        'extracted_text',
        'last_index_error',
        'indexed_at',
    ];

    protected $casts = [
        'effective_at' => 'date',
        'uploaded_at' => 'datetime',
        'indexed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(WasherKnowledgeChunk::class, 'document_id');
    }

    public function isCurrent(): bool
    {
        return $this->lifecycle_status === 'vigente';
    }
}
