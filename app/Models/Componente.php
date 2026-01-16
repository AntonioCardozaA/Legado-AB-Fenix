<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Componente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'codigo',
        'cantidad_total',
        'activo',
    ];

    /* =======================
     | Relaciones
     ======================= */

    public function analisisComponentes()
    {
        return $this->hasMany(AnalisisComponente::class);
    }
}
