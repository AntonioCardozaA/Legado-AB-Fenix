<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisisComponente extends Model
{
    use HasFactory;

    protected $table = 'analisis_componentes';

    protected $fillable = [
        'linea_id',
        'componente_id',
        'reductor',
        'fecha_analisis',
        'numero_orden',
        'actividad',
        'evidencia_fotos',
    ];

    protected $casts = [
        'evidencia_fotos' => 'array',
        'fecha_analisis' => 'date',
    ];

    /**
     * Relación con la línea (lavadora)
     */
    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    /**
     * Relación con el componente
     */
    public function componente()
    {
        return $this->belongsTo(Componente::class);
    }

    /**
     * Relación con análisis general (para el futuro)
     */
    public function analisisGeneral()
    {
        return $this->belongsTo(AnalisisGeneral::class);
    }

    /**
     * Obtener las fotos como array
     */
    public function getEvidenciaFotosAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Establecer las fotos como JSON
     */
    public function setEvidenciaFotosAttribute($value)
    {
        $this->attributes['evidencia_fotos'] = $value ? json_encode($value) : null;
    }

    /**
     * Scope para filtrar por línea
     */
    public function scopeLinea($query, $lineaId)
    {
        if ($lineaId) {
            return $query->where('linea_id', $lineaId);
        }
        return $query;
    }

    /**
     * Scope para filtrar por componente
     */
    public function scopeComponente($query, $componenteId)
    {
        if ($componenteId) {
            return $query->where('componente_id', $componenteId);
        }
        return $query;
    }

    /**
     * Scope para filtrar por reductor
     */
    public function scopeReductor($query, $reductor)
    {
        if ($reductor) {
            return $query->where('reductor', $reductor);
        }
        return $query;
    }

    /**
     * Scope para filtrar por mes y año
     */
    public function scopeMes($query, $fecha)
    {
        if ($fecha) {
            return $query->whereMonth('fecha_analisis', date('m', strtotime($fecha)))
                         ->whereYear('fecha_analisis', date('Y', strtotime($fecha)));
        }
        return $query;
    }
}
