<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Linea extends Model
{
    use HasFactory;

    protected $table = 'lineas';
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con análisis de componentes
     */
    public function analisisLavadora()
    {
        return $this->hasMany(AnalisisLavadora::class);
    }

    /**
     * Relación con componentes a través de análisis_componentes
     */
    public function componentes()
    {
        return $this->belongsToMany(Componente::class, 'analisis_componentes')
                    ->withPivot('actividad', 'reductor', 'numero_orden', 'evidencia_fotos')
                    ->withTimestamps();
    }

    /**
     * Scope para filtrar líneas activas
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
     public function planesAccion()
    {
        return $this->hasMany(PlanAccion::class, 'linea_id');
    }
}
