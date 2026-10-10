<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInteractionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'source_type',
        'source_id',
        'provider',
        'model',
        'status',
        'prompt_version',
        'response_time_ms',
        'input_chars',
        'output_chars',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'grounding_score',
        'valid_sources_count',
        'invalid_sources_count',
        'estimated_cost_usd',
        'usage',
        'metadata',
        'error_message',
    ];

    protected $casts = [
        'usage' => 'array',
        'metadata' => 'array',
        'response_time_ms' => 'integer',
        'input_chars' => 'integer',
        'output_chars' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'grounding_score' => 'float',
        'valid_sources_count' => 'integer',
        'invalid_sources_count' => 'integer',
        'estimated_cost_usd' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
