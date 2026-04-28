<?php

namespace App\Models;

use App\Support\HodometroHoras;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CadenaCiclo extends Model
{
    use HasFactory;

    protected $table = 'cadena_ciclos';

    protected $fillable = [
        'linea_id',
        'linea',
        'codigo',
        'numero_ciclo',
        'proveedor',
        'paso_inicial',
        'hodometro_inicial',
        'instalada_en',
        'retirada_en',
        'activa',
        'observaciones',
    ];

    protected $casts = [
        'numero_ciclo' => 'integer',
        'paso_inicial' => 'integer',
        'hodometro_inicial' => 'integer',
        'instalada_en' => 'datetime',
        'retirada_en' => 'datetime',
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lineaModel()
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public function elongaciones()
    {
        return $this->hasMany(Elongacion::class, 'cadena_ciclo_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activa', true);
    }

    public function scopePorLinea($query, string $linea)
    {
        return $query->where('linea', $linea);
    }

    public function getUltimoHodometroCicloAttribute(): ?int
    {
        return $this->elongaciones()->max('hodometro_ciclo');
    }

    public function getVidaUtilHorasAttribute(): ?int
    {
        return $this->ultimo_hodometro_ciclo;
    }

    public function getHodometroInicialFormateadoAttribute(): ?string
    {
        return HodometroHoras::formatear($this->hodometro_inicial);
    }
}
