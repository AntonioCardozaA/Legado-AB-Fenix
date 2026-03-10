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
        'componente',
        'lado',
        'fecha_analisis',
        'numero_orden',
        'estado',
        'actividad',
        'responsable',
        'observaciones',
        'evidencia_fotos',

        // Revisadas
        'revisadas_anillas',
        'revisadas_placas_perno',
        'revisadas_reglillas',
        'revisadas_rodamientos',
        'revisadas_excentricos',
        'revisadas_pistas',
        'revisadas_esparragos',

        // Totales
        'total_anillas',
        'total_placas_perno',
        'total_reglillas',
        'total_rodamientos',
        'total_excentricos',
        'total_pistas',
        'total_esparragos',

        // Análisis 52-12-4
        'valor_anterior_52',
        'valor_actual_52',
        'valor_anterior_12',
        'valor_actual_12',
        'valor_anterior_4',
        'valor_actual_4',

        // Plan PCM
        'plan_accion_pcm1',
        'plan_accion_pcm2',
        'plan_accion_pcm3',
        'plan_accion_pcm4',

        // *** NUEVOS CAMPOS PARA RESOLUCIÓN DE REGISTROS ***
        'resuelto_por_cambio',
        'fecha_resolucion',
        'nota_resolucion',
        'id_registro_que_resolvio',
    ];

    protected $casts = [
        'fecha_analisis' => 'date',
        'evidencia_fotos' => 'array',
        'plan_accion_pcm1' => 'array',
        'plan_accion_pcm2' => 'array',
        'plan_accion_pcm3' => 'array',
        'plan_accion_pcm4' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',

        // *** NUEVOS CAMPOS CON CAST ***
        'resuelto_por_cambio' => 'boolean',
        'fecha_resolucion' => 'datetime',
        'id_registro_que_resolvio' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | CONSTANTES
    |--------------------------------------------------------------------------
    */

    const COMPONENTES = [
        'ANILLAS' => 'Anillas (Ventanas-Cortinas)',
        'PLACAS_PERNO' => 'Placas Perno',
        'REGLILLAS' => 'Reglillas (Parrillas)',
        'RODAMIENTOS' => 'Rodamientos',
        'EXCENTRICOS' => 'Excéntricos - Levas',
        'PISTAS' => 'Pistas',
        'ESPARRAGOS' => 'Espárragos',
    ];

    const MODULOS = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16];

    const LADOS = [
        'VAPOR',
        'PASILLO'
    ];

    const ESTADOS = [
        'Buen estado',
        'Desgaste moderado',
        'Desgaste severo',
        'Dañado - Requiere cambio',
        'Cambiado'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    /**
     * Relación con el registro que resolvió este análisis
     */
    public function registroResolutor()
    {
        return $this->belongsTo(self::class, 'id_registro_que_resolvio');
    }

    /**
     * Relación con los registros que fueron resueltos por este análisis
     */
    public function registrosResueltos()
    {
        return $this->hasMany(self::class, 'id_registro_que_resolvio');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

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

    public function scopeDelMes($query)
    {
        return $query
            ->whereMonth('fecha_analisis', now()->month)
            ->whereYear('fecha_analisis', now()->year);
    }

    /**
     * Scope para obtener solo registros activos (no resueltos)
     */
    public function scopeActivos($query)
    {
        return $query->where('resuelto_por_cambio', false);
    }

    /**
     * Scope para obtener registros resueltos
     */
    public function scopeResueltos($query)
    {
        return $query->where('resuelto_por_cambio', true);
    }

    /**
     * Scope para obtener registros que requieren atención
     */
    public function scopeRequiereAtencion($query)
    {
        return $query->where('estado', 'Dañado - Requiere cambio')
                     ->where('resuelto_por_cambio', false);
    }

    /**
     * Scope para obtener registros cambiados
     */
    public function scopeCambiados($query)
    {
        return $query->where('estado', 'Cambiado');
    }

    /**
     * Scope para obtener registros resueltos en un período
     */
    public function scopeResueltosEntre($query, $inicio, $fin)
    {
        return $query->where('resuelto_por_cambio', true)
                     ->whereBetween('fecha_resolucion', [$inicio, $fin]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getComponenteNombreAttribute()
    {
        return self::COMPONENTES[$this->componente] ?? $this->componente;
    }

    public function getModuloNombreAttribute()
    {
        if ($this->componente === 'ANILLAS') {
            return "Anillas (Todos los módulos)";
        }

        return "Módulo {$this->modulo}";
    }

    public function getFechaFormateadaAttribute()
    {
        return $this->fecha_analisis
            ? $this->fecha_analisis->format('d/m/Y')
            : 'Sin fecha';
    }

    public function getHoraFormateadaAttribute()
    {
        return $this->created_at
            ? $this->created_at->format('H:i')
            : '';
    }

    public function getTieneImagenesAttribute()
    {
        return !empty($this->evidencia_fotos);
    }

    public function getCantidadImagenesAttribute()
    {
        return $this->evidencia_fotos
            ? count($this->evidencia_fotos)
            : 0;
    }

    /**
     * Obtener la fecha de resolución formateada
     */
    public function getFechaResolucionFormateadaAttribute()
    {
        return $this->fecha_resolucion
            ? $this->fecha_resolucion->format('d/m/Y H:i')
            : null;
    }

    /**
     * Obtener el tiempo transcurrido desde la resolución
     */
    public function getTiempoDesdeResolucionAttribute()
    {
        if (!$this->fecha_resolucion) {
            return null;
        }

        return $this->fecha_resolucion->diffForHumans();
    }

    /**
     * Verificar si es un registro de cambio
     */
    public function getEsCambioAttribute()
    {
        return $this->estado === 'Cambiado';
    }

    /**
     * Verificar si es un registro de daño
     */
    public function getEsDanioAttribute()
    {
        return $this->estado === 'Dañado - Requiere cambio';
    }

    /**
     * Obtener el estado con formato para badges
     */
    public function getEstadoBadgeAttribute()
    {
        return match ($this->estado) {
            'Buen estado' => [
                'class' => 'bg-green-100 text-green-800 border-green-200',
                'icon' => 'fa-check-circle',
                'text' => 'Buen estado'
            ],
            'Desgaste moderado' => [
                'class' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'icon' => 'fa-exclamation-triangle',
                'text' => 'Desgaste moderado'
            ],
            'Desgaste severo' => [
                'class' => 'bg-orange-100 text-orange-800 border-orange-200',
                'icon' => 'fa-exclamation-triangle',
                'text' => 'Desgaste severo'
            ],
            'Dañado - Requiere cambio' => [
                'class' => 'bg-red-100 text-red-800 border-red-200',
                'icon' => 'fa-times-circle',
                'text' => 'Requiere cambio'
            ],
            'Cambiado' => [
                'class' => 'bg-blue-100 text-blue-800 border-blue-200',
                'icon' => 'fa-exchange-alt',
                'text' => 'Cambiado'
            ],
            default => [
                'class' => 'bg-gray-100 text-gray-800 border-gray-200',
                'icon' => 'fa-question-circle',
                'text' => $this->estado
            ]
        };
    }

    /*
    |--------------------------------------------------------------------------
    | ICONOS Y COLORES
    |--------------------------------------------------------------------------
    */

    public function getLadoIconoAttribute()
    {
        return $this->lado === 'VAPOR'
            ? 'fa-wind'
            : 'fa-walking';
    }

    public function getLadoColorAttribute()
    {
        return $this->lado === 'VAPOR'
            ? 'red'
            : 'blue';
    }

    public function getLadoClaseAttribute()
    {
        return $this->lado === 'VAPOR'
            ? 'bg-red-100 text-red-800'
            : 'bg-blue-100 text-blue-800';
    }

    public function getEstadoIconoAttribute()
    {
        return match ($this->estado) {
            'Buen estado' => 'fa-check-circle text-green-600',
            'Desgaste moderado',
            'Desgaste severo' => 'fa-exclamation-triangle text-yellow-600',
            'Dañado - Requiere cambio' => 'fa-times-circle text-red-600',
            'Cambiado' => 'fa-exchange-alt text-blue-600',
            default => 'fa-question-circle text-gray-600',
        };
    }

    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            'Buen estado' => 'green',
            'Desgaste moderado', 'Desgaste severo' => 'yellow',
            'Dañado - Requiere cambio' => 'red',
            'Cambiado' => 'blue',
            default => 'gray',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | PROGRESO COMPONENTES
    |--------------------------------------------------------------------------
    */

    public function getTotalComponenteAttribute()
    {
        return match($this->componente) {
            'ANILLAS' => 256,
            default => 16
        };
    }

    public function getRevisadasComponenteAttribute()
    {
        return match($this->componente) {
            'ANILLAS' => $this->revisadas_anillas ?? 0,
            'PLACAS_PERNO' => $this->revisadas_placas_perno ?? 0,
            'REGLILLAS' => $this->revisadas_reglillas ?? 0,
            'RODAMIENTOS' => $this->revisadas_rodamientos ?? 0,
            'EXCENTRICOS' => $this->revisadas_excentricos ?? 0,
            'PISTAS' => $this->revisadas_pistas ?? 0,
            'ESPARRAGOS' => $this->revisadas_esparragos ?? 0,
            default => 0
        };
    }

    public function getPorcentajeAvanceAttribute()
    {
        $total = $this->total_componente;
        $revisadas = $this->revisadas_componente;

        if ($total > 0) {
            return round(($revisadas / $total) * 100, 1);
        }

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADISTICAS COMPLETAS
    |--------------------------------------------------------------------------
    */

    public function getEstadisticasCompletas()
    {
        $componentes = array_keys(self::COMPONENTES);
        $estadisticas = [];

        foreach ($componentes as $componente) {

            $campoRevisadas = 'revisadas_' . strtolower($componente);
            $campoTotal = 'total_' . strtolower($componente);

            if ($this->$campoRevisadas !== null && $this->$campoTotal !== null) {

                $total = $this->$campoTotal;
                $rev = $this->$campoRevisadas;

                $estadisticas[$componente] = [
                    'nombre' => self::COMPONENTES[$componente],
                    'revisadas' => $rev,
                    'total' => $total,
                    'porcentaje' => $total > 0
                        ? round(($rev / $total) * 100, 1)
                        : 0
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Obtener estadísticas de resolución
     */
    public function getEstadisticasResolucionAttribute()
    {
        $registrosResueltos = $this->registrosResueltos()->count();
        $tiempoPromedioResolucion = $this->registrosResueltos()
            ->whereNotNull('fecha_resolucion')
            ->get()
            ->avg(function($registro) {
                return $registro->fecha_analisis->diffInDays($registro->fecha_resolucion);
            });

        return [
            'total_resueltos' => $registrosResueltos,
            'tiempo_promedio_dias' => round($tiempoPromedioResolucion ?? 0, 1),
            'fecha_resolucion' => $this->fecha_resolucion_formateada,
            'nota' => $this->nota_resolucion
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ANALISIS 52 - 12 - 4
    |--------------------------------------------------------------------------
    */

    public function getAnalisis52124()
    {
        return [

            '52_semanas' => [
                'anterior' => $this->valor_anterior_52,
                'actual' => $this->valor_actual_52,
                'variacion' => $this->calcularVariacion(
                    $this->valor_anterior_52,
                    $this->valor_actual_52
                )
            ],

            '12_semanas' => [
                'anterior' => $this->valor_anterior_12,
                'actual' => $this->valor_actual_12,
                'variacion' => $this->calcularVariacion(
                    $this->valor_anterior_12,
                    $this->valor_actual_12
                )
            ],

            '4_semanas' => [
                'anterior' => $this->valor_anterior_4,
                'actual' => $this->valor_actual_4,
                'variacion' => $this->calcularVariacion(
                    $this->valor_anterior_4,
                    $this->valor_actual_4
                )
            ],
        ];
    }

    private function calcularVariacion($anterior, $actual)
    {
        if ($anterior === null || $actual === null) {
            return null;
        }

        if ($anterior == 0) {
            return 100;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    /*
    |--------------------------------------------------------------------------
    | UTILIDADES
    |--------------------------------------------------------------------------
    */

    public function isAnalisisCompleto()
    {
        return $this->revisadas_componente >= $this->total_componente;
    }

    public function resetearRevisadas()
    {
        $this->revisadas_anillas = 0;
        $this->revisadas_placas_perno = 0;
        $this->revisadas_reglillas = 0;
        $this->revisadas_rodamientos = 0;
        $this->revisadas_excentricos = 0;
        $this->revisadas_pistas = 0;
        $this->revisadas_esparragos = 0;

        $this->save();
    }

    /**
     * Marcar este registro como resuelto por otro análisis
     */
    public function marcarComoResuelto($registroResolutor, $nota = null)
    {
        $this->update([
            'resuelto_por_cambio' => true,
            'fecha_resolucion' => now(),
            'id_registro_que_resolvio' => $registroResolutor->id,
            'nota_resolucion' => $nota ?: "Resuelto por orden #{$registroResolutor->numero_orden}"
        ]);
    }

    /**
     * Verificar si este análisis es un cambio que resuelve daños anteriores
     */
    public function esCambioQueResuelveDanios()
    {
        if ($this->estado !== 'Cambiado') {
            return false;
        }

        return $this->registrosResueltos()->count() > 0;
    }

    /**
     * Obtener los daños pendientes para el mismo módulo y componente
     */
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

    /**
     * Obtener el historial completo de este módulo y componente
     */
    public function getHistorialCompleto()
    {
        return self::where('linea_id', $this->linea_id)
            ->where('modulo', $this->modulo)
            ->where('componente', $this->componente)
            ->orderBy('fecha_analisis', 'desc')
            ->get();
    }

    /**
     * Obtener estadísticas de vida útil del componente
     */
    public function getEstadisticasVidaUtil()
    {
        $historial = $this->getHistorialCompleto();
        
        $totalRegistros = $historial->count();
        $cambios = $historial->where('estado', 'Cambiado')->count();
        $danios = $historial->where('estado', 'Dañado - Requiere cambio')->count();
        
        $primerRegistro = $historial->last();
        $ultimoCambio = $historial->where('estado', 'Cambiado')->first();
        
        $diasVida = $primerRegistro 
            ? $this->fecha_analisis->diffInDays($primerRegistro->fecha_analisis) 
            : 0;
        
        return [
            'total_registros' => $totalRegistros,
            'veces_cambiado' => $cambios,
            'veces_danado' => $danios,
            'dias_vida' => $diasVida,
            'frecuencia_danos' => $totalRegistros > 0 
                ? round(($danios / $totalRegistros) * 100, 1) 
                : 0,
            'dias_desde_ultimo_cambio' => $ultimoCambio 
                ? $this->fecha_analisis->diffInDays($ultimoCambio->fecha_analisis) 
                : null,
            'confiabilidad' => $cambios > 0 
                ? round(($diasVida / $cambios) / 30, 1) 
                : 'N/A'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | EVENTOS DEL MODELO
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::created(function ($analisis) {
            // Log cuando se crea un análisis
            \Log::info("Nuevo análisis creado ID: {$analisis->id} - Estado: {$analisis->estado}");
        });

        static::updated(function ($analisis) {
            // Log cuando se actualiza un análisis
            if ($analisis->wasChanged('estado') && $analisis->estado === 'Cambiado') {
                \Log::info("Análisis ID: {$analisis->id} marcado como CAMBIADO");
            }
        });
    }
}