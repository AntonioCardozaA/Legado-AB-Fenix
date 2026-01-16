<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Elongacion extends Model
{
    protected $fillable = [
        'analisis_id',
        'horometro',
        'mediciones_bombas',
        'mediciones_vapor',
        'juego_rodaja_bombas',
        'juego_rodaja_vapor',
        'elongacion_bombas_mm',
        'elongacion_vapor_mm',
        'elongacion_bombas_pct',
        'elongacion_vapor_pct',
        'estado_bombas',
        'estado_vapor'
    ];


    protected $casts = [
        'mediciones_bombas' => 'array',
        'mediciones_vapor' => 'array'
    ];

    public function analisis()
    {
        return $this->belongsTo(Analisis::class);
    }
}

