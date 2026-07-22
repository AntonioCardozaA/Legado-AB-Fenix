<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceEvent extends Model
{
    use HasFactory;

    public const STATUS_DETECTED = 'detected';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PLAN_GENERATED = 'plan_generated';
    public const STATUS_REQUIRES_INFORMATION = 'requires_information';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_IGNORED = 'ignored';

    protected $fillable = [
        'linea_id',
        'componente_id',
        'source_type',
        'source_id',
        'event_type',
        'severity',
        'detected_value',
        'limit_value',
        'title',
        'description',
        'context_data',
        'status',
        'fingerprint',
        'detected_at',
        'resolved_at',
    ];

    protected $casts = [
        'context_data' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class, 'componente_id');
    }

    public function planesAccion(): HasMany
    {
        return $this->hasMany(PlanAccion::class, 'maintenance_event_id');
    }

    public function sourceUrl(): ?string
    {
        return match ($this->source_type) {
            'analisis_lavadora' => route('analisis-lavadora.show', ['analisislavadora' => $this->source_id]),
            'elongacion' => route('elongaciones.show', ['elongacion' => $this->source_id]),
            default => null,
        };
    }
}
