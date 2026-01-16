<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanAccion extends Model
{
    use HasFactory;

    protected $table = 'planes_accion';

    protected $fillable = [
        'paro_id',
        'actividad',
        'descripcion',
        'fecha_planeada',
        'fecha_ejecucion',
        'estado',
        'responsable_id',
        'plan_referencia',
        'encontro_dano',
        'observaciones_dano',
    ];

    protected $casts = [
        'fecha_planeada' => 'date',
        'fecha_ejecucion' => 'date',
        'encontro_dano' => 'boolean',
    ];

    /* =======================
     | Relaciones
     ======================= */

    public function paro()
    {
        return $this->belongsTo(Paro::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
