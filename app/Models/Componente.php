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
        'reductor',
        'ubicacion',
        'cantidad_total',
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
}
