<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'usuario_id',
        'evidencia_fotos',
    ];

    protected $casts = [
        'evidencia_fotos' => 'array',
        'fecha_analisis' => 'date',
    ];

    /**
     * Corrige fotos guardadas como JSON, array o texto.
     */
    public function getEvidenciaFotosAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_null($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return [$value];
    }

    /**
     * Guarda siempre las fotos como JSON válido.
     */
    public function setEvidenciaFotosAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['evidencia_fotos'] = json_encode([]);
            return;
        }

        if (is_array($value)) {
            $this->attributes['evidencia_fotos'] = json_encode(array_values($value));
            return;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            $this->attributes['evidencia_fotos'] = json_encode(array_values($decoded));
            return;
        }

        $this->attributes['evidencia_fotos'] = json_encode([$value]);
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function analisisGeneral(): BelongsTo
    {
        return $this->belongsTo(AnalisisGeneral::class);
    }

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

    public function scopeUltimosPorComponente(Builder $query): Builder
    {
        return $query->whereIn('id', function ($subQuery) {
            $subQuery->selectRaw('MAX(id)')
                ->from('analisis_componentes')
                ->groupBy(['linea_id', 'componente_id', 'reductor']);
        });
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
                    ->where(function ($query) {
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

    public function historialRestablecimientos(): HasMany
    {
        return $this->hasMany(HistorialRestablecimiento::class, 'analisis_id');
    }
}