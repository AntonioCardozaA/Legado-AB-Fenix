<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasherKnowledgeChunk extends Model
{
    use HasFactory;

    protected $table = 'washer_knowledge_chunks';

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'searchable_text',
        'token_count',
        'metadata',
        'embedding',
    ];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(WasherKnowledgeDocument::class, 'document_id');
    }
}
