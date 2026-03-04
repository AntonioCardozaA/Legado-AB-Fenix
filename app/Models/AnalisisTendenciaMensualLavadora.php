<?php
// app/Models/AnalisisTendenciaMensualLavadora.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AnalisisTendenciaMensualLavadora extends Model
{
    protected $table = 'analisis_tendencia_mensual_lavadoras';
    
    protected $fillable = [
        'linea_id',
        'anio',
        'mes',
        'total_danos_52_semanas',
        'total_danos_12_semanas',
        'total_danos_4_semanas',
        'fecha_corte_52',
        'fecha_corte_12',
        'fecha_corte_4',
        'observaciones'
    ];

    protected $casts = [
        'fecha_corte_52' => 'date',
        'fecha_corte_12' => 'date',
        'fecha_corte_4' => 'date',
        'total_danos_52_semanas' => 'decimal:2',
        'total_danos_12_semanas' => 'decimal:2',
        'total_danos_4_semanas' => 'decimal:2'
    ];

    /**
     * Relación con la línea (lavadora)
     */
    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }

    /**
     * Obtener el mes en formato texto
     */
    public function getMesNombreAttribute(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[$this->mes] ?? 'Desconocido';
    }

    /**
     * Obtener período formateado
     */
    public function getPeriodoAttribute(): string
    {
        return $this->mesNombre . ' ' . $this->anio;
    }

    /**
     * Obtener el registro del mes anterior
     */
    public function getMesAnteriorAttribute()
    {
        $fechaActual = Carbon::create($this->anio, $this->mes, 1);
        $fechaAnterior = $fechaActual->copy()->subMonth();
        
        return self::where('linea_id', $this->linea_id)
            ->where('anio', $fechaAnterior->year)
            ->where('mes', $fechaAnterior->month)
            ->first();
    }

    /**
     * Calcular variación con mes anterior
     */
    public function getVariacion52SemanasAttribute(): ?array
    {
        $anterior = $this->mes_anterior;
        if (!$anterior) return null;
        
        $diferencia = $this->total_danos_52_semanas - $anterior->total_danos_52_semanas;
        $porcentaje = $anterior->total_danos_52_semanas > 0 
            ? round(($diferencia / $anterior->total_danos_52_semanas) * 100, 2)
            : ($diferencia != 0 ? 100 : 0);
        
        return [
            'diferencia' => $diferencia,
            'porcentaje' => $porcentaje,
            'tendencia' => $diferencia > 0 ? 'up' : ($diferencia < 0 ? 'down' : 'stable'),
            'color' => $diferencia > 0 ? 'danger' : ($diferencia < 0 ? 'success' : 'warning')
        ];
    }

    /**
     * Calcular variación con mes anterior para 12 semanas
     */
    public function getVariacion12SemanasAttribute(): ?array
    {
        $anterior = $this->mes_anterior;
        if (!$anterior) return null;
        
        $diferencia = $this->total_danos_12_semanas - $anterior->total_danos_12_semanas;
        $porcentaje = $anterior->total_danos_12_semanas > 0 
            ? round(($diferencia / $anterior->total_danos_12_semanas) * 100, 2)
            : ($diferencia != 0 ? 100 : 0);
        
        return [
            'diferencia' => $diferencia,
            'porcentaje' => $porcentaje,
            'tendencia' => $diferencia > 0 ? 'up' : ($diferencia < 0 ? 'down' : 'stable'),
            'color' => $diferencia > 0 ? 'danger' : ($diferencia < 0 ? 'success' : 'warning')
        ];
    }

    /**
     * Calcular variación con mes anterior para 4 semanas
     */
    public function getVariacion4SemanasAttribute(): ?array
    {
        $anterior = $this->mes_anterior;
        if (!$anterior) return null;
        
        $diferencia = $this->total_danos_4_semanas - $anterior->total_danos_4_semanas;
        $porcentaje = $anterior->total_danos_4_semanas > 0 
            ? round(($diferencia / $anterior->total_danos_4_semanas) * 100, 2)
            : ($diferencia != 0 ? 100 : 0);
        
        return [
            'diferencia' => $diferencia,
            'porcentaje' => $porcentaje,
            'tendencia' => $diferencia > 0 ? 'up' : ($diferencia < 0 ? 'down' : 'stable'),
            'color' => $diferencia > 0 ? 'danger' : ($diferencia < 0 ? 'success' : 'warning')
        ];
    }

    /**
     * Formatear número con 2 decimales
     */
    public function getTotal52FormateadoAttribute(): string
    {
        return number_format($this->total_danos_52_semanas, 2);
    }

    public function getTotal12FormateadoAttribute(): string
    {
        return number_format($this->total_danos_12_semanas, 2);
    }

    public function getTotal4FormateadoAttribute(): string
    {
        return number_format($this->total_danos_4_semanas, 2);
    }
}