<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property int $id
 * @property int|null $linea_id
 * @property int|null $componente_id
 * @property string|null $reductor
 * @property Carbon|null $fecha_analisis
 * @property array<int, string>|null $evidencia_fotos
 * @property Linea|null $linea
 * @property Componente|null $componente
 */
class AnalisisLavadora extends Model
{
    use HasFactory;

    protected $table = 'analisis_componentes';

    protected $fillable = [
        'linea_id',
        'componente_id',
        'reductor',
        'lado',
        'fecha_analisis',
        'numero_orden',
        'estado',
        'actividad',
        'evidencia_fotos',
    ];

    protected $casts = [
        'evidencia_fotos' => 'array',
        'fecha_analisis' => 'date',
    ];

    /**
     * Relación con la línea (lavadora)
     */
    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }

    /**
     * Relación con el componente
     */
    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class);
    }

    /**
     * Relación futura
     */
    public function analisisGeneral(): BelongsTo
    {
        return $this->belongsTo(AnalisisGeneral::class);
    }

    /**
     * Scopes
     */
    public function scopeLinea(Builder $query, mixed $lineaId): Builder
    {
        if ($lineaId) {
            return $query->where('linea_id', $lineaId);
        }
        return $query;
    }

    public function scopeComponente(Builder $query, mixed $componenteId): Builder
    {
        if ($componenteId) {
            return $query->where('componente_id', $componenteId);
        }
        return $query;
    }

    public function scopeReductor(Builder $query, mixed $reductor): Builder
    {
        if ($reductor) {
            return $query->where('reductor', $reductor);
        }
        return $query;
    }

    public function scopeMes(Builder $query, mixed $fecha): Builder
    {
        if ($fecha) {
            return $query->whereMonth('fecha_analisis', date('m', strtotime($fecha)))
                         ->whereYear('fecha_analisis', date('Y', strtotime($fecha)));
        }
        return $query;
    }
    public function planAccion(): HasMany
    {
        return $this->hasMany(PlanAccion::class);
    }

    public function getNombreCompletoAttribute()
    {
    return 'Línea ' . $this->linea_id;
    }


    public function getActividadesPendientesAttribute()
    {
        return $this->planAccion()
                    ->whereIn('estado', ['pendiente', 'en_proceso'])
                    ->count();
    }

    public function getProximasActividadesAttribute()
    {
        return $this->planAccion()
                    ->where(function($query) {
                        $query->whereDate('fecha_pcm1', '>=', now())
                              ->orWhereDate('fecha_pcm2', '>=', now())
                              ->orWhereDate('fecha_pcm3', '>=', now())
                              ->orWhereDate('fecha_pcm4', '>=', now());
                    })
                    ->where('estado', '!=', 'completada')
                    ->orderByRaw('LEAST(
                        COALESCE(fecha_pcm1, "9999-12-31"),
                        COALESCE(fecha_pcm2, "9999-12-31"),
                        COALESCE(fecha_pcm3, "9999-12-31"),
                        COALESCE(fecha_pcm4, "9999-12-31")
                    ) ASC')
                    ->limit(5)
                    ->get();
    }
        /**
     * Relación con el historial de restablecimientos
     */
    public function historialRestablecimientos(): HasMany
    {
        return $this->hasMany(HistorialRestablecimiento::class, 'analisis_id');
    }
}
