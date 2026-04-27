<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AnalisisPasteurizadora extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'analisis_pasteurizadora';

    protected $fillable = [
        'linea_id',
        'modulo',
        'nivel',
        'componente',
        'lado',
        'fecha_analisis',
        'numero_orden',
        'estado',
        'actividad',
        'responsable',
        'observaciones',
        'evidencia_fotos',
        'cantidad_componentes_revisados',
        'componentes_revisados',
        'total_componentes',
        'valor_anterior_52',
        'valor_actual_52',
        'valor_anterior_12',
        'valor_actual_12',
        'valor_anterior_4',
        'valor_actual_4',
        'plan_accion_pcm1',
        'plan_accion_pcm2',
        'plan_accion_pcm3',
        'plan_accion_pcm4',
        'resuelto_por_cambio',
        'fecha_resolucion',
        'nota_resolucion',
        'id_registro_que_resolvio',
    ];

    protected $casts = [
        'fecha_analisis' => 'date',
        'evidencia_fotos' => 'array',
        'cantidad_componentes_revisados' => 'integer',
        'componentes_revisados' => 'array',
        'total_componentes' => 'integer',
        'plan_accion_pcm1' => 'array',
        'plan_accion_pcm2' => 'array',
        'plan_accion_pcm3' => 'array',
        'plan_accion_pcm4' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'resuelto_por_cambio' => 'boolean',
        'fecha_resolucion' => 'datetime',
        'id_registro_que_resolvio' => 'integer',
    ];

    // ============================================================
    // CONFIGURACIÃ“N DE PASTEURIZADORES
    // ============================================================

    const PASTEURIZADORES = [
        'P-03' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-04' => ['tipo' => 'sencillo', 'modulos' => 12],
        'P-05' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-06' => ['tipo' => 'doble', 'modulos' => 16],
        'P-07' => ['tipo' => 'doble', 'modulos' => 16],
        'P-08' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-09' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-10' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-11' => ['tipo' => 'doble', 'modulos' => 16],
        'P-12' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-13' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-14' => ['tipo' => 'sencillo', 'modulos' => 9],
    ];

    const COMPONENTES_SENCILLOS = [
        'ANILLAS' => ['nombre' => 'Anillas (Ventanas-Cortinas)', 'cantidad' => 3],
        'EXCENTRICOS' => ['nombre' => 'Excéntricos', 'cantidad' => 2],
        'PISTAS' => ['nombre' => 'Pistas', 'cantidad' => 2],
        'VIGAS_FIJAS' => ['nombre' => 'Vigas Fijas', 'cantidad' => 4],
        'VIGA_MOVIMIENTO' => ['nombre' => 'Viga de Movimiento', 'cantidad' => 1],
        'PLACAS_PERNO' => ['nombre' => 'Placas Perno', 'cantidad' => 3],
        'ESPARRAGOS' => ['nombre' => 'Esparragos', 'cantidad' => 2],

    ];

    const COMPONENTES_DOBLES = [
        'ANILLAS' => ['nombre' => 'Anillas (Ventanas-Cortinas)', 'cantidad' => 5],
        'EXCENTRICOS' => ['nombre' => 'Excéntricos', 'cantidad' => 2],
        'RODAJAS' => ['nombre' => 'Rodajas', 'cantidad' => 2],
        'PLACAS_PERNO' => ['nombre' => 'Placas Perno', 'cantidad' => 5],
        'VIGAS_MOVIMIENTO' => ['nombre' => 'Vigas de Movimiento', 'cantidad' => 2],
        'PISTAS' => ['nombre' => 'Pistas', 'cantidad' => 4],
        'ESPARRAGOS' => ['nombre' => 'Esparragos', 'cantidad' => 4],
    ];

    const LADOS = ['VAPOR', 'PASILLO'];
    const NIVELES = ['SUPERIOR', 'INFERIOR'];
    const ESTADOS = ['Buen estado', 'Desgaste moderado', 'Desgaste severo', 'Dañado - Requiere cambio', 'Cambiado'];

    // ============================================================
    // MÃ‰TODOS DE CONFIGURACIÃ“N
    // ============================================================

    public static function getComponentesPorLinea($lineaNombre)
    {
        $tipo = self::PASTEURIZADORES[$lineaNombre]['tipo'] ?? null;
        if ($tipo === 'sencillo') {
            return self::COMPONENTES_SENCILLOS;
        } elseif ($tipo === 'doble') {
            return self::COMPONENTES_DOBLES;
        }
        return [];
    }

    public static function getModulosPorLinea($lineaNombre)
    {
        return self::PASTEURIZADORES[$lineaNombre]['modulos'] ?? 0;
    }

    public static function resolveComponentePorLinea($lineaNombre, $componente)
    {
        if (!$lineaNombre || !$componente) {
            return null;
        }

        $componentes = self::getComponentesPorLinea($lineaNombre);
        $componenteKey = strtoupper($componente);

        if (isset($componentes[$componenteKey])) {
            return [
                'key' => $componenteKey,
                'config' => $componentes[$componenteKey],
            ];
        }

        foreach ($componentes as $key => $config) {
            if (strtoupper($key) === $componenteKey) {
                return [
                    'key' => $key,
                    'config' => $config,
                ];
            }
        }

        return null;
    }

    public static function getTotalComponentesPorLineaYComponente($lineaNombre, $componente): int
    {
        $resolved = self::resolveComponentePorLinea($lineaNombre, $componente);
        return (int) ($resolved['config']['cantidad'] ?? 0);
    }

    public function getTotalComponentesPorComponente(): int
    {
        return self::getTotalComponentesPorLineaYComponente($this->linea?->nombre, $this->componente);
    }

    public static function normalizarComponentesRevisados($value, ?int $totalComponentes = null): array
    {
        $componentes = $value;

        if (is_string($componentes) && trim($componentes) !== '') {
            $decoded = json_decode($componentes, true);
            $componentes = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($componentes)) {
            return [];
        }

        return collect($componentes)
            ->map(fn($item) => is_numeric($item) ? (int) $item : null)
            ->filter(fn($item) => $item !== null && $item > 0 && ($totalComponentes === null || $item <= $totalComponentes))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function getComponentesRevisadosRegistrados($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null): array
    {
        $linea = Linea::find($lineaId);
        $totalComponentes = self::getTotalComponentesPorLineaYComponente($linea?->nombre, $componente);

        $query = self::where('linea_id', $lineaId)
            ->where('modulo', $modulo)
            ->where('componente', $componente);

        if ($lado) {
            $query->where('lado', $lado);
        }

        if ($nivel) {
            $query->where('nivel', $nivel);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query
            ->get()
            ->flatMap(function ($registro) use ($totalComponentes) {
                $componentes = self::normalizarComponentesRevisados($registro->componentes_revisados, $totalComponentes);

                if (!empty($componentes)) {
                    return $componentes;
                }

                $cantidad = min((int) ($registro->cantidad_componentes_revisados ?? 0), $totalComponentes);

                return $cantidad > 0 ? range(1, $cantidad) : [];
            })
            ->map(fn($item) => (int) $item)
            ->filter(fn($item) => $item > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function getComponentesPendientes($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null): array
    {
        $linea = Linea::find($lineaId);
        $totalComponentes = self::getTotalComponentesPorLineaYComponente($linea?->nombre, $componente);

        if ($totalComponentes <= 0) {
            return [];
        }

        return array_values(array_diff(
            range(1, $totalComponentes),
            self::getComponentesRevisadosRegistrados($lineaId, $modulo, $componente, $lado, $nivel, $excludeId)
        ));
    }

    // ============================================================
    // MÃ‰TODOS PARA CONTEO POR LADO Y NIVEL
    // ============================================================

    public static function getCantidadComponentesRevisados($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null): int
    {
        return count(self::getComponentesRevisadosRegistrados($lineaId, $modulo, $componente, $lado, $nivel, $excludeId));
    }

    public static function getCantidadComponentesPendientes($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null): int
    {
        $linea = Linea::find($lineaId);
        if (!$linea) {
            return 0;
        }

        $total = self::getTotalComponentesPorLineaYComponente($linea->nombre, $componente);
        $alreadyReviewed = self::getCantidadComponentesRevisados($lineaId, $modulo, $componente, $lado, $nivel, $excludeId);

        return max(0, $total - $alreadyReviewed);
    }

    public static function getComponentesYaRevisados($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null): array
    {
        return self::getComponentesRevisadosRegistrados($lineaId, $modulo, $componente, $lado, $nivel, $excludeId);
    }

    public static function getLadosPendientes($lineaId, $modulo, $componente, $nivel = null)
    {
        $ladosPendientes = [];

        foreach (self::LADOS as $lado) {
            $remaining = self::getCantidadComponentesPendientes($lineaId, $modulo, $componente, $lado, $nivel);
            if ($remaining > 0) {
                $ladosPendientes[] = $lado;
            }
        }

        return $ladosPendientes;
    }

    // ============================================================
    // MÃ‰TODOS PARA GESTIÃ“N DE LADOS Y NIVELES
    // ============================================================

    /**
     * Obtiene el siguiente lado a revisar para un nivel específico
     * @return string|null El siguiente lado (VAPOR o PASILLO) o null si ambos están completos
     */
    public static function getSiguienteLado($lineaId, $modulo, $componente, $ladoActual = null, $nivel = null)
    {
        $ladosPendientes = self::getLadosPendientes($lineaId, $modulo, $componente, $nivel);

        if (empty($ladosPendientes)) {
            return null; // Ambos lados están completos para este nivel
        }

        if (!$ladoActual) {
            return reset($ladosPendientes); // Retorna el primer lado pendiente
        }

        // Si el lado actual es VAPOR, intenta PASILLO
        if ($ladoActual === 'VAPOR') {
            return in_array('PASILLO', $ladosPendientes) ? 'PASILLO' : null;
        }

        // Si el lado actual es PASILLO, intenta VAPOR
        if ($ladoActual === 'PASILLO') {
            return in_array('VAPOR', $ladosPendientes) ? 'VAPOR' : null;
        }

        return null;
    }

    public static function getSiguienteRevisionContexto($lineaId, $modulo, $componente, $nivelActual = null, $ladoActual = null)
    {
        $niveles = self::NIVELES;

        if ($nivelActual && in_array($nivelActual, $niveles, true)) {
            $siguienteLado = self::getSiguienteLado($lineaId, $modulo, $componente, $ladoActual, $nivelActual);

            if ($siguienteLado) {
                return [
                    'nivel' => $nivelActual,
                    'lado' => $siguienteLado,
                ];
            }

            $indiceActual = array_search($nivelActual, $niveles, true);
            if ($indiceActual !== false) {
                $niveles = array_slice($niveles, $indiceActual + 1);
            }
        }

        foreach ($niveles as $nivel) {
            $ladosPendientes = self::getLadosPendientes($lineaId, $modulo, $componente, $nivel);

            if (!empty($ladosPendientes)) {
                return [
                    'nivel' => $nivel,
                    'lado' => reset($ladosPendientes),
                ];
            }
        }

        return null;
    }

    /**
     * Obtiene el siguiente nivel a revisar
     * @return string|null El siguiente nivel (SUPERIOR o INFERIOR) o null si ambos están completos
     */
    public static function getSiguienteNivel($lineaId, $modulo, $componente, $nivelActual = null)
    {
        $siguiente = self::getSiguienteRevisionContexto($lineaId, $modulo, $componente, $nivelActual);

        if (!$siguiente) {
            return null;
        }

        return $siguiente['nivel'] !== $nivelActual ? $siguiente['nivel'] : null;
    }

    /**
     * Verifica si un nivel está completamente revisado
     */
    public static function nivelCompletado($lineaId, $modulo, $componente, $nivel)
    {
        $ladosPendientes = self::getLadosPendientes($lineaId, $modulo, $componente, $nivel);
        return empty($ladosPendientes);
    }

    /**
     * Obtiene información del estado de revisión de lados y niveles
     */
    public static function getEstadoRevision($lineaId, $modulo, $componente, $nivel = null)
    {
        $niveles = self::NIVELES;
        $estado = [];

        foreach ($niveles as $niv) {
            $estado[$niv] = [
                'completado' => self::nivelCompletado($lineaId, $modulo, $componente, $niv),
                'lados_pendientes' => self::getLadosPendientes($lineaId, $modulo, $componente, $niv)
            ];
        }

        return $estado;
    }

    // ============================================================
    // RELACIONES
    // ============================================================

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    public function registroResolutor()
    {
        return $this->belongsTo(self::class, 'id_registro_que_resolvio');
    }

    public function registrosResueltos()
    {
        return $this->hasMany(self::class, 'id_registro_que_resolvio');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePorLinea($query, $lineaId)
    {
        return $query->where('linea_id', $lineaId);
    }

    public function scopePorModulo($query, $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function scopePorComponente($query, $componente)
    {
        return $query->where('componente', $componente);
    }

    public function scopePorLado($query, $lado)
    {
        return $query->where('lado', $lado);
    }

    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_analisis', [$inicio, $fin]);
    }

    public function scopeActivos($query)
    {
        return $query->where('resuelto_por_cambio', false);
    }

    public function scopeResueltos($query)
    {
        return $query->where('resuelto_por_cambio', true);
    }

    public function scopeRequiereAtencion($query)
    {
        return $query->where('estado', 'DaÃ±ado - Requiere cambio')->where('resuelto_por_cambio', false);
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getComponenteNombreAttribute()
    {
        $lineaNombre = $this->linea ? $this->linea->nombre : null;
        $componentes = self::getComponentesPorLinea($lineaNombre);
        return $componentes[$this->componente]['nombre'] ?? $this->componente;
    }

    public function getModuloNombreAttribute()
    {
        return "Módulo {$this->modulo}";
    }

    public function getFechaFormateadaAttribute()
    {
        return $this->fecha_analisis ? $this->fecha_analisis->format('d/m/Y') : 'Sin fecha';
    }

    public function getHoraFormateadaAttribute()
    {
        return $this->created_at ? $this->created_at->format('H:i') : '';
    }

    public function getTieneImagenesAttribute()
    {
        return !empty($this->evidencia_fotos);
    }

    public function getCantidadImagenesAttribute()
    {
        return $this->evidencia_fotos ? count($this->evidencia_fotos) : 0;
    }

    public function getFechaResolucionFormateadaAttribute()
    {
        return $this->fecha_resolucion ? $this->fecha_resolucion->format('d/m/Y H:i') : null;
    }

    public function getEsCambioAttribute()
    {
        return $this->estado === 'Cambiado';
    }

    public function getEsDanioAttribute()
    {
        return $this->estado === 'DaÃ±ado - Requiere cambio';
    }

    public function getLadoIconoAttribute()
    {
        return $this->lado === 'VAPOR' ? 'fa-wind' : 'fa-walking';
    }

    public function getLadoColorAttribute()
    {
        return $this->lado === 'VAPOR' ? 'red' : 'blue';
    }

    public function getLadoClaseAttribute()
    {
        return $this->lado === 'VAPOR' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';
    }

    public function getEstadoBadgeAttribute()
    {
        return match ($this->estado) {
            'Buen estado' => ['class' => 'bg-green-100 text-green-800', 'icon' => 'fa-check-circle'],
            'Desgaste moderado', 'Desgaste severo' => ['class' => 'bg-yellow-100 text-yellow-800', 'icon' => 'fa-exclamation-triangle'],
            'DaÃ±ado - Requiere cambio' => ['class' => 'bg-red-100 text-red-800', 'icon' => 'fa-times-circle'],
            'Cambiado' => ['class' => 'bg-blue-100 text-blue-800', 'icon' => 'fa-exchange-alt'],
            default => ['class' => 'bg-gray-100 text-gray-800', 'icon' => 'fa-question-circle'],
        };
    }

    public function getPorcentajeAvanceAttribute()
    {
        $total = $this->total_componentes ?? 0;
        $revisadas = count(self::normalizarComponentesRevisados($this->componentes_revisados, $total));

        if ($revisadas === 0) {
            $revisadas = $this->cantidad_componentes_revisados ?? 0;
        }

        if ($total > 0) {
            return round(($revisadas / $total) * 100, 1);
        }
        return 0;
    }

    // ============================================================
    // MÃ‰TODOS DE UTILIDAD
    // ============================================================

    public function isAnalisisCompleto()
    {
        $total = $this->total_componentes ?? 0;
        $revisadas = $this->cantidad_componentes_revisados ?? 0;
        return $revisadas >= $total;
    }

    public function marcarComoResuelto($registroResolutor, $nota = null)
    {
        $this->update([
            'resuelto_por_cambio' => true,
            'fecha_resolucion' => now(),
            'id_registro_que_resolvio' => $registroResolutor->id,
            'nota_resolucion' => $nota ?: "Resuelto por orden #{$registroResolutor->numero_orden}"
        ]);
    }

    public function getDaniosPendientes()
    {
        return self::where('linea_id', $this->linea_id)
            ->where('modulo', $this->modulo)
            ->where('componente', $this->componente)
            ->where('estado', 'DaÃ±ado - Requiere cambio')
            ->where('resuelto_por_cambio', false)
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function getHistorialCompleto()
    {
        return self::where('linea_id', $this->linea_id)
            ->where('modulo', $this->modulo)
            ->where('componente', $this->componente)
            ->orderBy('fecha_analisis', 'desc')
            ->get();
    }

    // ============================================================
    // ANÃLISIS 52-12-4
    // ============================================================

    public function getAnalisis52124()
    {
        return [
            '52_semanas' => [
                'anterior' => $this->valor_anterior_52,
                'actual' => $this->valor_actual_52,
                'variacion' => $this->calcularVariacion($this->valor_anterior_52, $this->valor_actual_52)
            ],
            '12_semanas' => [
                'anterior' => $this->valor_anterior_12,
                'actual' => $this->valor_actual_12,
                'variacion' => $this->calcularVariacion($this->valor_anterior_12, $this->valor_actual_12)
            ],
            '4_semanas' => [
                'anterior' => $this->valor_anterior_4,
                'actual' => $this->valor_actual_4,
                'variacion' => $this->calcularVariacion($this->valor_anterior_4, $this->valor_actual_4)
            ],
        ];
    }

    private function calcularVariacion($anterior, $actual)
    {
        if ($anterior === null || $actual === null) return null;
        if ($anterior == 0) return 100;
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    // ============================================================
    // EVENTOS
    // ============================================================

    protected static function booted()
    {
        static::created(function ($analisis) {
            \Log::info("Nuevo análisis creado ID: {$analisis->id}");
            event(new \App\Events\AnalisisPasteurizadoraCreado($analisis));
        });

        static::updated(function ($analisis) {
            \Log::info("Análisis actualizado ID: {$analisis->id}");
            event(new \App\Events\AnalisisPasteurizadoraCreado($analisis));
        });
    }

    public function setComponentesRevisadosAttribute($value)
    {
        $componentes = self::normalizarComponentesRevisados($value, $this->attributes['total_componentes'] ?? null);

        $this->attributes['componentes_revisados'] = json_encode($componentes);
        $this->attributes['cantidad_componentes_revisados'] = count($componentes);
    }
}
