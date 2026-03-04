<?php
// app/Models/HistorialRestablecimiento.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialRestablecimiento extends Model
{
    use HasFactory;

    protected $table = 'historial_restablecimientos';

    protected $fillable = [
        'analisis_id',
        'linea_id',
        'componente_id',
        'reductor',
        'lado',
        'fecha_analisis_original',
        'fecha_restablecimiento',
        'motivo',
        'periodo_meses',
    ];

    protected $casts = [
        'fecha_analisis_original' => 'date',
        'fecha_restablecimiento' => 'datetime',
    ];

    public function analisis()
    {
        return $this->belongsTo(AnalisisLavadora::class, 'analisis_id');
    }

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    public function componente()
    {
        return $this->belongsTo(Componente::class);
    }
}