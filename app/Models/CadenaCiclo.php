<?php

namespace App\Models;

use App\Support\HodometroHoras;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function costEntries(): HasMany
    {
        return $this->hasMany(LavadoraCostEntry::class, 'cadena_ciclo_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activa', true);
    }

    public function scopeActualesPorLinea($query, ?string $linea = null)
    {
        return $query->whereIn(
            $this->qualifyColumn('id'),
            static::currentActiveIdsQuery($linea)
        );
    }

    public function scopePorLinea($query, string $linea)
    {
        return $query->where('linea', $linea);
    }

    public static function currentActiveIdsQuery(?string $linea = null)
    {
        return static::query()
            ->selectRaw('MAX(id) as id')
            ->where('activa', true)
            ->when($linea, static fn ($query, string $lineaFiltrada) => $query->where('linea', $lineaFiltrada))
            ->groupBy('linea');
    }

    public static function currentActiveForLine(string $linea): ?self
    {
        return static::query()
            ->whereIn('id', static::currentActiveIdsQuery($linea))
            ->first();
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
