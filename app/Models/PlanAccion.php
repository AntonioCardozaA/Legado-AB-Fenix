<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PlanAccion extends Model
{
    use HasFactory;

    protected $table = 'plan_accion';

    protected $fillable = [
        'linea_id',
        'actividad',
        'fecha_pcm1',
        'fecha_pcm2',
        'fecha_pcm3',
        'fecha_pcm4',
        'estado',
        'observaciones',
        'notificacion_enviada',
        'fecha_recordatorio',
        'responsable_id',
        'tipo_maquina' // Agregar este campo
    ];

    protected $casts = [
        'fecha_pcm1' => 'date',
        'fecha_pcm2' => 'date',
        'fecha_pcm3' => 'date',
        'fecha_pcm4' => 'date',
        'fecha_recordatorio' => 'date',
        'tipo_maquina' => 'array' // Laravel convertirá automáticamente JSON a array
    ];

    public function linea()
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
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
        $hoy = Carbon::now();
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
            'dias_restantes' => $hoy->diffInDays($proximaFecha, false)
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
}