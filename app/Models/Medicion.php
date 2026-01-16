<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Medicion extends Model
{
    use HasFactory;

    protected $fillable = [
        'analisis_id',
        'tipo',
        'medicion_1',
        'medicion_2',
        'medicion_3',
        'medicion_4',
        'medicion_5',
        'medicion_6',
        'medicion_7',
        'medicion_8',
        'promedio',
    ];

    protected $casts = [
        'medicion_1' => 'decimal:2',
        'medicion_2' => 'decimal:2',
        'medicion_3' => 'decimal:2',
        'medicion_4' => 'decimal:2',
        'medicion_5' => 'decimal:2',
        'medicion_6' => 'decimal:2',
        'medicion_7' => 'decimal:2',
        'medicion_8' => 'decimal:2',
        'promedio'   => 'decimal:2',
    ];

    /* =======================
     | Relaciones
     ======================= */

    public function analisis()
    {
        return $this->belongsTo(Analisis::class);
    }
}
