<?php

namespace App\Models;

use App\Models\Concerns\UppercasesActividad;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $linea_id
 * @property int|null $responsable_id
 * @property int|null $registrado_por_id
 * @property int|null $ejecutado_por_id
 * @property string|null $actividad
 * @property Carbon|null $fecha_pcm1
 * @property Carbon|null $fecha_pcm2
 * @property Carbon|null $fecha_pcm3
 * @property Carbon|null $fecha_pcm4
 * @property Carbon|null $fecha_ejecucion
 * @property string|null $estado
 * @property string|null $tipo_equipo
 * @property string|null $area_pasteurizadora
 * @property bool $completado
 * @property array<int, string>|null $tipo_maquina
 */
class PlanAccion extends Model
{
    use HasFactory, UppercasesActividad;

    protected $table = 'plan_accion';

    protected $fillable = [
        'linea_id',
        'actividad',
        'tipo_equipo',
        'area_pasteurizadora',
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
        'tipo_maquina' // Agregar este campo
    ];

    protected $casts = [
        'area_pasteurizadora' => 'string',
        'fecha_pcm1' => 'date',
        'fecha_pcm2' => 'date',
        'fecha_pcm3' => 'date',
        'fecha_pcm4' => 'date',
        'fecha_recordatorio' => 'date',
        'fecha_ejecucion' => 'datetime',
        'tipo_maquina' => 'array', // Laravel convertirá automáticamente JSON a array
        'completado' => 'boolean',
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

    // Método helper para verificar si tiene un tipo específico
    public function tieneTipoMaquina($tipo)
    {
        return $this->tipo_maquina && in_array($tipo, $this->tipo_maquina);
    }

    // Getter para obtener los tipos formateados para mostrar
    public function getTiposMaquinaFormateadosAttribute()
    {
        if (!$this->tipo_maquina) {
            return collect([]);
        }

        $iconos = [
            'lavadora' => ['icon' => 'fa-tshirt', 'color' => 'blue'],
            'pasteurizadora' => ['icon' => 'fa-temperature-high', 'color' => 'red'],
            'enjuagadora' => ['icon' => 'fa-wind', 'color' => 'cyan'],
            'otros' => ['icon' => 'fa-cog', 'color' => 'gray'],
        ];

        return collect($this->tipo_maquina)->map(function($tipo) use ($iconos) {
            return [
                'nombre' => $tipo,
                'nombre_mostrar' => ucfirst($tipo),
                'icono' => $iconos[$tipo]['icon'] ?? 'fa-cog',
                'color' => $iconos[$tipo]['color'] ?? 'gray'
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

    public function getFechasProgramadasAttribute()
    {
        $fechas = [];
        if ($this->fecha_pcm1) $fechas['pcm1'] = $this->fecha_pcm1;
        if ($this->fecha_pcm2) $fechas['pcm2'] = $this->fecha_pcm2;
        if ($this->fecha_pcm3) $fechas['pcm3'] = $this->fecha_pcm3;
        if ($this->fecha_pcm4) $fechas['pcm4'] = $this->fecha_pcm4;
        return $fechas;
    }

    public function getProximaFechaAttribute()
    {
        $hoy = Carbon::now()->startOfDay();
        $proximaFecha = null;
        $proximaPcm = null;
        
        foreach ($this->fechas_programadas as $pcm => $fecha) {
            if ($fecha >= $hoy) {
                if (!$proximaFecha || $fecha < $proximaFecha) {
                    $proximaFecha = $fecha;
                    $proximaPcm = $pcm;
                }
            }
        }
        
        return $proximaFecha ? [
            'fecha' => $proximaFecha,
            'pcm' => $proximaPcm,
            'dias_restantes' => (int) $hoy->diffInDays(Carbon::parse($proximaFecha)->startOfDay(), false)
        ] : null;
    }

    public function getDiasParaVencimientoAttribute()
    {
        $proxima = $this->proxima_fecha;
        return $proxima ? $proxima['dias_restantes'] : null;
    }

    public function actualizarEstado()
    {
        $hoy = Carbon::now();
        $tieneVencidas = false;
        $tienePendientes = false;
        $todasCompletadas = true;
        
        foreach ($this->fechas_programadas as $fecha) {
            if ($fecha) {
                $todasCompletadas = false;
                if ($fecha < $hoy) {
                    $tieneVencidas = true;
                } else {
                    $tienePendientes = true;
                }
            }
        }
        
        if ($todasCompletadas) {
            $this->estado = 'completada';
        } elseif ($tieneVencidas && !$tienePendientes) {
            $this->estado = 'atrasada';
        } elseif ($tieneVencidas && $tienePendientes) {
            $this->estado = 'en_proceso';
        } else {
            $this->estado = 'pendiente';
        }
        
        $this->saveQuietly();
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

}
