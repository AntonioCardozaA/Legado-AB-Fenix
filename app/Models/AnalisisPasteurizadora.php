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
        'revisadas_piezas',
        'componentes_revisados',
        'total_piezas',
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
        'componentes_revisados' => 'array',
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
    // CONFIGURACIÓN DE PASTEURIZADORES
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
        'ESPARRAGOS' => ['nombre' => 'Espárragos', 'cantidad' => 2],
        
    ];

    const COMPONENTES_DOBLES = [
        'ANILLAS' => ['nombre' => 'Anillas (Ventanas-Cortinas)', 'cantidad' => 5],
        'EXCENTRICOS' => ['nombre' => 'Excéntricos', 'cantidad' => 2],
        'RODAJAS' => ['nombre' => 'Rodajas', 'cantidad' => 2],
        'PLACAS_PERNO' => ['nombre' => 'Placas Perno', 'cantidad' => 5],
        'VIGAS_MOVIMIENTO' => ['nombre' => 'Vigas de Movimiento', 'cantidad' => 2],
        'PISTAS' => ['nombre' => 'Pistas', 'cantidad' => 4],
        'ESPARRAGOS' => ['nombre' => 'Espárragos', 'cantidad' => 4],
    ];

    const LADOS = ['VAPOR', 'PASILLO'];
    const ESTADOS = ['Buen estado', 'Desgaste moderado', 'Desgaste severo', 'Dañado - Requiere cambio', 'Cambiado'];

    // ============================================================
    // MÉTODOS DE CONFIGURACIÓN
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

    public function getTotalPiezasPorComponente()
    {
        $lineaNombre = $this->linea ? $this->linea->nombre : null;
        $componentes = self::getComponentesPorLinea($lineaNombre);
        return $componentes[$this->componente]['cantidad'] ?? 0;
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
        return $query->where('estado', 'Dañado - Requiere cambio')->where('resuelto_por_cambio', false);
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
        return $this->estado === 'Dañado - Requiere cambio';
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
            'Dañado - Requiere cambio' => ['class' => 'bg-red-100 text-red-800', 'icon' => 'fa-times-circle'],
            'Cambiado' => ['class' => 'bg-blue-100 text-blue-800', 'icon' => 'fa-exchange-alt'],
            default => ['class' => 'bg-gray-100 text-gray-800', 'icon' => 'fa-question-circle'],
        };
    }

   public function getPorcentajeAvanceAttribute()
    {
        $total = $this->total_piezas ?? 0;
        
        // Priorizar componentes_revisados sobre revisadas_piezas
        if ($this->componentes_revisados && is_array($this->componentes_revisados)) {
            $revisadas = count($this->componentes_revisados);
        } else {
            $revisadas = $this->revisadas_piezas ?? 0;
        }
        
        if ($total > 0) {
            return round(($revisadas / $total) * 100, 1);
        }
        return 0;
    }

    // ============================================================
    // MÉTODOS DE UTILIDAD
    // ============================================================
    
    public function isAnalisisCompleto()
    {
        $total = $this->total_piezas ?? 0;
        $revisadas = $this->revisadas_piezas ?? 0;
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
            ->where('estado', 'Dañado - Requiere cambio')
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
    // ANÁLISIS 52-12-4
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

            // Disparar evento para actualizar histórico de revisados
            event(new \App\Events\AnalisisPasteurizadoraCreado($analisis));
        });

        static::updated(function ($analisis) {
            \Log::info("Análisis actualizado ID: {$analisis->id}");

            // Disparar evento para actualizar histórico de revisados
            event(new \App\Events\AnalisisPasteurizadoraCreado($analisis));
        });
    }
    public function setComponentesRevisadosAttribute($value)
    {
        $this->attributes['componentes_revisados'] = is_array($value) ? json_encode($value) : $value;
        
        // Actualizar automáticamente revisadas_piezas
        if (is_array($value)) {
            $this->attributes['revisadas_piezas'] = count($value);
        } elseif (is_string($value) && $value !== null) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $this->attributes['revisadas_piezas'] = count($decoded);
            }
        }
    }
}