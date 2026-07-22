<?php

namespace App\Models;

use App\Models\Concerns\UppercasesActividad;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $linea_id
 * @property int|null $maintenance_event_id
 * @property int|null $responsable_id
 * @property int|null $registrado_por_id
 * @property int|null $ejecutado_por_id
 * @property int|null $reviewed_by
 * @property string|null $actividad
 * @property string|null $source
 * @property string|null $estado
 * @property string|null $tipo_equipo
 * @property string|null $area_pasteurizadora
 * @property string|null $priority_level
 * @property string|null $maintenance_type
 * @property Carbon|null $fecha_pcm1
 * @property Carbon|null $fecha_pcm2
 * @property Carbon|null $fecha_pcm3
 * @property Carbon|null $fecha_pcm4
 * @property Carbon|null $fecha_ejecucion
 * @property Carbon|null $generated_at
 * @property Carbon|null $reviewed_at
 * @property bool $completado
 * @property array<int, string>|null $tipo_maquina
 * @property array<string, mixed>|null $original_generated_content
 * @property array<string, mixed>|null $approved_content
 */
class PlanAccion extends Model
{
    use HasFactory;
    use UppercasesActividad;

    protected $table = 'plan_accion';

    protected $fillable = [
        'linea_id',
        'actividad',
        'source',
        'maintenance_event_id',
        'tipo_equipo',
        'area_pasteurizadora',
        'priority_level',
        'maintenance_type',
        'detected_problem',
        'technical_justification',
        'risk_if_not_executed',
        'missing_information',
        'ai_provider',
        'ai_model',
        'ai_original_response',
        'original_generated_content',
        'approved_content',
        'knowledge_sources',
        'source_metadata',
        'review_history',
        'confidence_level',
        'prompt_version',
        'prompt_snapshot',
        'generated_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'fecha_pcm1',
        'fecha_pcm2',
        'fecha_pcm3',
        'fecha_pcm4',
        'estado',
        'observaciones',
        'notificacion_enviada',
        'fecha_recordatorio',
        'responsable_id',
        'registrado_por_id',
        'ejecutado_por_id',
        'fecha_ejecucion',
        'tipo_maquina',
        'estimated_cost_total',
        'actual_cost_total',
        'estimated_hours',
        'actual_hours',
        'execution_result',
        'effectiveness',
        'final_observations',
    ];

    protected $casts = [
        'area_pasteurizadora' => 'string',
        'missing_information' => 'array',
        'ai_original_response' => 'array',
        'original_generated_content' => 'array',
        'approved_content' => 'array',
        'knowledge_sources' => 'array',
        'source_metadata' => 'array',
        'review_history' => 'array',
        'prompt_snapshot' => 'array',
        'fecha_pcm1' => 'date',
        'fecha_pcm2' => 'date',
        'fecha_pcm3' => 'date',
        'fecha_pcm4' => 'date',
        'fecha_recordatorio' => 'date',
        'fecha_ejecucion' => 'datetime',
        'generated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'tipo_maquina' => 'array',
        'completado' => 'boolean',
        'confidence_level' => 'float',
        'estimated_cost_total' => 'float',
        'actual_cost_total' => 'float',
        'estimated_hours' => 'float',
        'actual_hours' => 'float',
    ];

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function ejecutadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ejecutado_por_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function maintenanceEvent(): BelongsTo
    {
        return $this->belongsTo(MaintenanceEvent::class, 'maintenance_event_id');
    }

    public function tieneTipoMaquina($tipo): bool
    {
        return is_array($this->tipo_maquina) && in_array($tipo, $this->tipo_maquina, true);
    }

    public function getTiposMaquinaFormateadosAttribute()
    {
        if (!$this->tipo_maquina) {
            return collect([]);
        }

        $iconos = [
            'lavadora' => ['icon' => 'fa-tshirt', 'color' => 'blue'],
            'etiquetadora' => ['icon' => 'fa-tags', 'color' => 'green'],
            'pasteurizadora' => ['icon' => 'fa-temperature-high', 'color' => 'red'],
            'enjuagadora' => ['icon' => 'fa-wind', 'color' => 'cyan'],
            'otros' => ['icon' => 'fa-cog', 'color' => 'gray'],
        ];

        return collect($this->tipo_maquina)->map(function ($tipo) use ($iconos) {
            return [
                'nombre' => $tipo,
                'nombre_mostrar' => ucfirst($tipo),
                'icono' => $iconos[$tipo]['icon'] ?? 'fa-cog',
                'color' => $iconos[$tipo]['color'] ?? 'gray',
            ];
        });
    }

    public static function areasPasteurizadoraOpciones(): array
    {
        return [
            AnalisisPasteurizadora::AREA_MECANICA => 'Mecanica',
            AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA => 'Hidraulica',
        ];
    }

    public static function normalizarAreaPasteurizadora(?string $area): string
    {
        return AnalisisPasteurizadora::normalizarArea($area);
    }

    public function getAreaPasteurizadoraLabelAttribute(): ?string
    {
        if (!$this->area_pasteurizadora) {
            return null;
        }

        return self::areasPasteurizadoraOpciones()[$this->area_pasteurizadora] ?? $this->area_pasteurizadora;
    }

    public function getFechasProgramadasAttribute(): array
    {
        $fechas = [];

        if ($this->fecha_pcm1) {
            $fechas['pcm1'] = $this->fecha_pcm1;
        }

        if ($this->fecha_pcm2) {
            $fechas['pcm2'] = $this->fecha_pcm2;
        }

        if ($this->fecha_pcm3) {
            $fechas['pcm3'] = $this->fecha_pcm3;
        }

        if ($this->fecha_pcm4) {
            $fechas['pcm4'] = $this->fecha_pcm4;
        }

        return $fechas;
    }

    public function getProximaFechaAttribute(): ?array
    {
        $hoy = Carbon::now()->startOfDay();
        $proximaFecha = null;
        $proximaPcm = null;

        foreach ($this->fechas_programadas as $pcm => $fecha) {
            if ($fecha >= $hoy && (!$proximaFecha || $fecha < $proximaFecha)) {
                $proximaFecha = $fecha;
                $proximaPcm = $pcm;
            }
        }

        if (!$proximaFecha) {
            return null;
        }

        return [
            'fecha' => $proximaFecha,
            'pcm' => $proximaPcm,
            'dias_restantes' => (int) $hoy->diffInDays(Carbon::parse($proximaFecha)->startOfDay(), false),
        ];
    }

    public function getDiasParaVencimientoAttribute(): ?int
    {
        $proxima = $this->proxima_fecha;

        return $proxima ? $proxima['dias_restantes'] : null;
    }

    public function actualizarEstado(): void
    {
        $hoy = Carbon::now();
        $tieneVencidas = false;
        $tienePendientes = false;
        $todasCompletadas = true;

        foreach ($this->fechas_programadas as $fecha) {
            if (!$fecha) {
                continue;
            }

            $todasCompletadas = false;

            if ($fecha < $hoy) {
                $tieneVencidas = true;
            } else {
                $tienePendientes = true;
            }
        }

        if ($todasCompletadas) {
            $this->estado = 'completada';
        } elseif ($tieneVencidas && !$tienePendientes) {
            $this->estado = 'atrasada';
        } elseif ($tieneVencidas) {
            $this->estado = 'en_proceso';
        } else {
            $this->estado = 'pendiente';
        }

        $this->saveQuietly();
    }

    public function isAiSuggested(): bool
    {
        return $this->source === 'ai';
    }

    public function currentStructuredContent(): ?array
    {
        return $this->approved_content ?: $this->original_generated_content;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    public function appendReviewHistory(array $entry): void
    {
        $history = $this->review_history ?? [];
        $history[] = $entry;
        $this->review_history = $history;
    }

    public function sourceLabel(): string
    {
        return $this->source === 'ai' ? 'Generado por IA' : 'Manual';
    }

    public function scopeActivas($query)
    {
        return $query->where('completado', false);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('completado', true);
    }

    public function scopePorTipoEquipo($query, string $tipoEquipo)
    {
        return $query->where('tipo_equipo', $tipoEquipo);
    }

    public function scopeAiSuggested($query)
    {
        return $query->where('source', 'ai');
    }
}
