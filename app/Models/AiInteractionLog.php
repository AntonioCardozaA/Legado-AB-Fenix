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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
