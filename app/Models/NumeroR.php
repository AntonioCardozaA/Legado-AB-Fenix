<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumeroR extends Model
{
    use HasFactory;

    protected $table = 'numeros_r';
    
    protected $fillable = [
        'categoria_id', // aquí puedes usar la relación con Linea si quieres
        'codigo',
        'descripcion',
        'activo',
    ];

    /* =======================
       Relaciones
    ======================= */
    
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function componentes()
{
    return $this->hasMany(Componente::class, 'numero_r_id', 'id');
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
       Scopes
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
       Accessors
    ======================= */

    public function getNombreCompletoAttribute()
    {
        return "{$this->codigo} - {$this->descripcion}";
    }
}
