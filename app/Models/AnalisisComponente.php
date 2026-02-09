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
        'estado',
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
     * Relación futura
     */
    public function analisisGeneral()
    {
        return $this->belongsTo(AnalisisGeneral::class);
    }

    /**
     * Scopes
     */
    public function scopeLinea($query, $lineaId)
    {
        if ($lineaId) {
            return $query->where('linea_id', $lineaId);
        }
        return $query;
    }

    public function scopeComponente($query, $componenteId)
    {
        if ($componenteId) {
            return $query->where('componente_id', $componenteId);
        }
        return $query;
    }

    public function scopeReductor($query, $reductor)
    {
        if ($reductor) {
            return $query->where('reductor', $reductor);
        }
        return $query;
    }

    public function scopeMes($query, $fecha)
    {
        if ($fecha) {
            return $query->whereMonth('fecha_analisis', date('m', strtotime($fecha)))
                         ->whereYear('fecha_analisis', date('Y', strtotime($fecha)));
        }
        return $query;
    }
}
