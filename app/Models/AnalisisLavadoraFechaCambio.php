<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalisisLavadoraFechaCambio extends Model
{
    use HasFactory;

    protected $table = 'analisis_lavadora_fecha_cambios';

    protected $fillable = [
        'analisis_lavadora_id',
        'usuario_id',
        'fecha_anterior',
        'fecha_nueva',
        'fecha_cambio',
    ];

    protected $casts = [
        'fecha_anterior' => 'date',
        'fecha_nueva' => 'date',
        'fecha_cambio' => 'datetime',
    ];

    public function analisisLavadora(): BelongsTo
    {
        return $this->belongsTo(AnalisisLavadora::class, 'analisis_lavadora_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
