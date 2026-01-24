<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NumeroR extends Model
{
    use HasFactory;

    protected $table = 'numeros_r';
    
    protected $fillable = [
        'categoria_id',
        'codigo',
        'descripcion',
        'activo',
    ];

    /* =======================
     | Relaciones
     ======================= */

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function analisis()
    {
        return $this->hasMany(Analisis::class);
    }

    public function componentesAnalisis()
    {
        return $this->hasMany(AnalisisComponente::class);
    }

    /* =======================
     | Scopes
     ======================= */

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    /* =======================
     | Accessors
     ======================= */

    public function getNombreCompletoAttribute()
    {
        return "{$this->codigo} - {$this->descripcion}";
    }
}