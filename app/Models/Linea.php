<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Linea extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activa',
    ];

    /* =======================
     | Relaciones
     ======================= */

    public function analisis()
    {
        return $this->hasMany(Analisis::class);
    }

    public function paros()
    {
        return $this->hasMany(Paro::class);
    }
}
