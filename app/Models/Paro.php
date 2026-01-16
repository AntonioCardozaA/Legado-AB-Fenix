<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Paro extends Model
{
    use HasFactory;

    protected $fillable = [
        'linea_id',
        'fecha_inicio',
        'fecha_fin',
        'tipo',
        'supervisor_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /* =======================
     | Relaciones
     ======================= */

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function planesAccion()
    {
        return $this->hasMany(PlanAccion::class);
    }
}
