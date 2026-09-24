<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PasteurizadoraKnowledgeDocument extends Model
{
    use HasFactory;

    protected $table = 'pasteurizadora_knowledge_documents';

    protected $fillable = [
        'linea_id',
        'area',
        'componente_id',
        'central_componente_id',
        'configuracion_id',
        'component_code',
        'component_name',
        'modulo',
        'nivel',
        'piso',
        'lado',
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
        'modulo' => 'integer',
        'configuracion_id' => 'integer',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function centralComponente(): BelongsTo
    {
        return $this->belongsTo(CentralHidraulicaComponente::class, 'central_componente_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(PasteurizadoraKnowledgeChunk::class, 'document_id');
    }

    public function isCurrent(): bool
    {
        return $this->lifecycle_status === 'vigente';
    }
}
