<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnalisisComponente extends Model
{
    use HasFactory;

    protected $table = 'analisis_componentes';

    protected $fillable = [
        'analisis_id',
        'componente_id',
        'cantidad_revisada',
        'estado',
        'actividad',
        'observaciones',
        'evidencia_fotos',
    ];

    protected $casts = [
        'evidencia_fotos' => 'array',
    ];

    /* =======================
     | Relaciones
     ======================= */

    public function analisis()
    {
        return $this->belongsTo(Analisis::class);
    }

    public function componente()
    {
        return $this->belongsTo(Componente::class);
    }
}