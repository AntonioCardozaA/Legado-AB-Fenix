<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Componente extends Model
{
    use HasFactory;

    protected $table = 'componentes';
    
    protected $fillable = [
        'nombre',
        'codigo',
        'linea',
        'reductor',
        'ubicacion',
        'grupo',
        'mecanismo',
        'cantidad_total',
        'cantidad_original',
        'tipo_equipo',
        'numero_r_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lineas()
    {
        return $this->belongsToMany(Linea::class, 'analisis_componentes')
                    ->withPivot('estado', 'actividad', 'observaciones')
                    ->withTimestamps();
    }

    public function lineasEtiquetadora()
    {
        return $this->belongsToMany(Linea::class, 'analisis_etiquetadora')
                    ->withPivot('estado', 'actividad', 'reductor', 'maquina', 'numero_orden', 'evidencia_fotos')
                    ->withTimestamps();
    }

    public function analisis()
    {
        return $this->hasMany(Analisis::class);
    }

    // 🔥 ESTA ERA LA QUE FALTABA
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function getReductoresAttribute()
    {
        return $this->reductor ? explode(',', $this->reductor) : [];
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeTipoEquipo($query, string $tipoEquipo)
    {
        return $query->where('tipo_equipo', $tipoEquipo);
    }

    public function scopePorLinea($query, $lineaId)
    {
        return $query->whereHas('analisis', function($q) use ($lineaId) {
            $q->where('linea_id', $lineaId);
        });
    }
    public function numeroR()
    {
        return $this->belongsTo(NumeroR::class, 'numero_r_id', 'id');
    }
    public function analisisLavadora()
    {
        return $this->hasMany(\App\Models\AnalisisLavadora::class, 'componente_id');
    }

    public function analisisEtiquetadora()
    {
        return $this->hasMany(\App\Models\AnalisisEtiquetadora::class, 'componente_id');
    }
}
