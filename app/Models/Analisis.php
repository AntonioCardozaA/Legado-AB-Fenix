<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Analisis extends Model
{
    use HasFactory;

    protected $table = 'analisis';

    protected $fillable = [
        'linea_id',
        'fecha_analisis',
        'numero_orden',
        'horometro',
        'elongacion_promedio',
        'juego_rodaja',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_analisis' => 'date',
    ];

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    public function componentes()
    {
        return $this->hasMany(AnalisisComponente::class);
    }

    public function mediciones()
    {
        return $this->hasMany(Medicion::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}