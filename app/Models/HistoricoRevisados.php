<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class HistoricoRevisados extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'historico_revisados';

    protected $fillable = [
        'area',
        'linea_id',
        'componente',
        'componente_nombre',
        'cantidad_total',
        'cantidad_revisada',
        'porcentaje',
        'ultima_revision',
        'proximo_vencimiento',
        'tipo_pasteurizadora',
        'estado',
    ];

    protected $casts = [
        'area' => 'string',
        'ultima_revision' => 'date',
        'proximo_vencimiento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePorLinea($query, $lineaId)
    {
        return $query->where('linea_id', $lineaId);
    }

    public function scopePorComponente($query, $componente)
    {
        return $query->where('componente', $componente);
    }

    public function scopeTipoPasteurizadora($query, $tipo)
    {
        return $query->where('tipo_pasteurizadora', $tipo);
    }

    public function scopeForArea($query, ?string $area = null)
    {
        $area = AnalisisPasteurizadora::normalizarArea($area);

        if ($area === AnalisisPasteurizadora::AREA_MECANICA) {
            return $query->where(function ($subQuery) {
                $subQuery->where('area', AnalisisPasteurizadora::AREA_MECANICA)
                    ->orWhereNull('area');
            });
        }

        return $query->where('area', $area);
    }

    public function scopeConVencimiento($query)
    {
        return $query->whereNotNull('proximo_vencimiento');
    }

    // ============================================================
    // ACCESORIOS
    // ============================================================

    public function getUltimaRevisionFormateadaAttribute()
    {
        return $this->ultima_revision
            ? $this->ultima_revision->format('d/m/Y')
            : 'Sin revisión';
    }

    public function getProximoVencimientoFormateadoAttribute()
    {
        if (!$this->proximo_vencimiento) {
            return 'Sin asignar';
        }

        $ahora = Carbon::now();
        if ($this->proximo_vencimiento < $ahora) {
            return 'Vencido';
        }

        return $this->proximo_vencimiento->format('d/m/Y H:i');
    }

    public function getDiasRestantesAttribute()
    {
        if (!$this->proximo_vencimiento) {
            return null;
        }

        return Carbon::now()->diffInDays($this->proximo_vencimiento, false);
    }

    public function getColorEstadoAttribute()
    {
        if ($this->porcentaje >= 80) {
            return 'success';
        } elseif ($this->porcentaje >= 50) {
            return 'info';
        } elseif ($this->porcentaje >= 20) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    // ============================================================
    // MÃ‰TODOS
    // ============================================================

    /**
     * Actualizar el conteo basándose en análisis de pasteurizadora
     */
    public static function actualizarDesdePasteurizadora($linea, $componente, ?string $area = null)
    {
        $area = AnalisisPasteurizadora::normalizarArea($area);
        $componentesConfig = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);

        if (!isset($componentesConfig[$componente])) {
            return null;
        }

        $cantidadTotal = AnalisisPasteurizadora::esBrazoTorsion($componente)
            ? AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea->nombre)
            : ($componentesConfig[$componente]['cantidad'] ?? 0);

        // Buscar todos los análisis de este componente
        $analisis = AnalisisPasteurizadora::queryForArea($area)
            ->where('linea_id', $linea->id)
            ->where('componente', $componente)
            ->get();

        // Contar componentes revisados
        $cantidadRevisada = $analisis->sum('cantidad_componentes_revisados');
        $cantidadRevisada = min($cantidadRevisada, $cantidadTotal);

        // El porcentaje
        $porcentaje = $cantidadTotal > 0
            ? round(($cantidadRevisada / $cantidadTotal) * 100, 1)
            : 0;

        // La última revisión
        $ultimoAnalisis = $analisis->sortByDesc('created_at')->first();
        $ultimaRevision = $ultimoAnalisis
            ? $ultimoAnalisis->fecha_analisis ?? $ultimoAnalisis->created_at->toDate()
            : null;

        // Tipo de pasteurizadora
        $tipoPasteurizadora = AnalisisPasteurizadora::PASTEURIZADORES[$linea->nombre]['tipo'] ?? 'sencillo';

        // Crear o actualizar el registro histórico
        $historico = self::updateOrCreate(
            [
                'linea_id' => $linea->id,
                'componente' => $componente,
                'area' => $area,
            ],
            [
                'componente_nombre' => $componentesConfig[$componente]['nombre'],
                'cantidad_total' => $cantidadTotal,
                'cantidad_revisada' => $cantidadRevisada,
                'porcentaje' => $porcentaje,
                'ultima_revision' => $ultimaRevision,
                'tipo_pasteurizadora' => $tipoPasteurizadora,
                'estado' => $cantidadRevisada >= $cantidadTotal ? 'completo' : 'en_progreso',
            ]
        );

        return $historico;
    }

    /**
     * Actualizar todos los componentes de una línea
     */
    public static function actualizarTodosComponentesLinea($lineaId, ?string $area = null)
    {
        $area = AnalisisPasteurizadora::normalizarArea($area);
        $linea = Linea::find($lineaId);
        if (!$linea) return null;

        $componentes = AnalisisPasteurizadora::getComponentesPorLinea($linea->nombre);

        foreach (array_keys($componentes) as $componente) {
            self::actualizarDesdePasteurizadora($linea, $componente, $area);
        }

        return true;
    }

    /**
     * Obtener resumen de una línea
     */
    public static function obtenerResumenLinea($lineaId, ?string $area = null)
    {
        $registros = self::forArea($area)->where('linea_id', $lineaId)->get();

        return [
            'total_componentes' => $registros->count(),
            'total_general' => $registros->sum('cantidad_total'),
            'total_revisado' => $registros->sum('cantidad_revisada'),
            'porcentaje_general' => $registros->sum('cantidad_total') > 0
                ? round(($registros->sum('cantidad_revisada') / $registros->sum('cantidad_total')) * 100, 1)
                : 0,
            'componentes_completos' => $registros->where('estado', 'completo')->count(),
        ];
    }
}
