<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    /* =======================
     | Relaciones
     ======================= */

    public function numerosR()
    {
        return $this->hasMany(NumeroR::class);
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

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}