<?php
// app/Models/AnalisisPasteurizadora.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalisisPasteurizadora extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'analisis_pasteurizadoras';

    protected $fillable = [
        'linea',
        'modulo',
        'componente',
        'fecha',
        'actividad',
        'cantidad',
        'valor_anterior_52',
        'valor_actual_52',
        'valor_anterior_12',
        'valor_actual_12',
        'valor_anterior_4',
        'valor_actual_4',
        'total_anillas',
        'revisadas_anillas',
        'total_placas',
        'revisadas_placas',
        'total_parrillas',
        'revisadas_parrillas',
        'total_rodamientos',
        'revisadas_rodamientos',
        'total_excentricos',
        'revisadas_excentricos',
        'total_reglillas',
        'revisadas_reglillas',
        'plan_accion_pcm1',
        'plan_accion_pcm2',
        'plan_accion_pcm3',
        'plan_accion_pcm4',
        'fotos',
        'observaciones',
        'responsable',
        'estado'
    ];

    protected $casts = [
        'fecha' => 'date',
        'fotos' => 'array',
        'plan_accion_pcm1' => 'array',
        'plan_accion_pcm2' => 'array',
        'plan_accion_pcm3' => 'array',
        'plan_accion_pcm4' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const COMPONENTES = [
        'anillas_pernos' => 'Anillas / Pernos de ojo',
        'placas_perno' => 'Placas perno',
        'parrillas' => 'Parrillas',
        'rodamientos' => 'Rodamientos',
        'excentricos_levas' => 'Excéntricos / Levas',
        'reglillas' => 'Reglillas'
    ];

    const MODULOS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

    const LINEAS = ['L-07', 'L-08'];

    const ESTADOS = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En Proceso',
        'completado' => 'Completado'
    ];

    // Scopes
    public function scopePorLinea($query, $linea)
    {
        return $query->where('linea', $linea);
    }

    public function scopePorComponente($query, $componente)
    {
        return $query->where('componente', $componente);
    }

    public function scopePorModulo($query, $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    // Accessors
    public function getComponenteNombreAttribute()
    {
        return self::COMPONENTES[$this->componente] ?? $this->componente;
    }

    public function getAvanceAnillasAttribute()
    {
        return $this->total_anillas > 0 
            ? round(($this->revisadas_anillas / $this->total_anillas) * 100, 2) 
            : 0;
    }

    public function getAvancePlacasAttribute()
    {
        return $this->total_placas > 0 
            ? round(($this->revisadas_placas / $this->total_placas) * 100, 2) 
            : 0;
    }

    public function getAvanceParrillasAttribute()
    {
        return $this->total_parrillas > 0 
            ? round(($this->revisadas_parrillas / $this->total_parrillas) * 100, 2) 
            : 0;
    }

    public function getAvanceRodamientosAttribute()
    {
        return $this->total_rodamientos > 0 
            ? round(($this->revisadas_rodamientos / $this->total_rodamientos) * 100, 2) 
            : 0;
    }

    public function getAvanceExcentricosAttribute()
    {
        return $this->total_excentricos > 0 
            ? round(($this->revisadas_excentricos / $this->total_excentricos) * 100, 2) 
            : 0;
    }

    public function getAvanceReglillasAttribute()
    {
        return $this->total_reglillas > 0 
            ? round(($this->revisadas_reglillas / $this->total_reglillas) * 100, 2) 
            : 0;
    }

    public function getVariacion52Attribute()
    {
        if ($this->valor_anterior_52 && $this->valor_anterior_52 > 0) {
            $variacion = (($this->valor_actual_52 - $this->valor_anterior_52) / $this->valor_anterior_52) * 100;
            return round($variacion, 2);
        }
        return 0;
    }

    public function getVariacion12Attribute()
    {
        if ($this->valor_anterior_12 && $this->valor_anterior_12 > 0) {
            $variacion = (($this->valor_actual_12 - $this->valor_anterior_12) / $this->valor_anterior_12) * 100;
            return round($variacion, 2);
        }
        return 0;
    }

    public function getVariacion4Attribute()
    {
        if ($this->valor_anterior_4 && $this->valor_anterior_4 > 0) {
            $variacion = (($this->valor_actual_4 - $this->valor_anterior_4) / $this->valor_anterior_4) * 100;
            return round($variacion, 2);
        }
        return 0;
    }

    public function getFechaFormateadaAttribute()
    {
        return $this->fecha ? $this->fecha->format('d/m/Y') : '';
    }

    // Methods
    public function actualizarRevisadas($componente, $cantidad)
    {
        $campo = 'revisadas_' . $componente;
        if (in_array($campo, $this->fillable)) {
            $this->$campo += $cantidad;
            if ($this->$campo > $this->{'total_' . $componente}) {
                $this->$campo = $this->{'total_' . $componente};
            }
            $this->save();
        }
    }

    public function getEstadisticasCompletas()
    {
        return [
            'anillas' => [
                'total' => $this->total_anillas,
                'revisadas' => $this->revisadas_anillas,
                'avance' => $this->avance_anillas
            ],
            'placas' => [
                'total' => $this->total_placas,
                'revisadas' => $this->revisadas_placas,
                'avance' => $this->avance_placas
            ],
            'parrillas' => [
                'total' => $this->total_parrillas,
                'revisadas' => $this->revisadas_parrillas,
                'avance' => $this->avance_parrillas
            ],
            'rodamientos' => [
                'total' => $this->total_rodamientos,
                'revisadas' => $this->revisadas_rodamientos,
                'avance' => $this->avance_rodamientos
            ],
            'excentricos' => [
                'total' => $this->total_excentricos,
                'revisadas' => $this->revisadas_excentricos,
                'avance' => $this->avance_excentricos
            ],
            'reglillas' => [
                'total' => $this->total_reglillas,
                'revisadas' => $this->revisadas_reglillas,
                'avance' => $this->avance_reglillas
            ]
        ];
    }

    public function getAnalisis52124()
    {
        return [
            'componente_52' => [
                'anterior' => $this->valor_anterior_52 ?? 0.51,
                'actual' => $this->valor_actual_52 ?? 0.69,
                'variacion' => $this->variacion52
            ],
            'componente_12' => [
                'anterior' => $this->valor_anterior_12 ?? 0.25,
                'actual' => $this->valor_actual_12 ?? 1.08,
                'variacion' => $this->variacion12
            ],
            'componente_4' => [
                'anterior' => $this->valor_anterior_4 ?? 0.23,
                'actual' => $this->valor_actual_4 ?? 2.52,
                'variacion' => $this->variacion4
            ]
        ];
    }
}