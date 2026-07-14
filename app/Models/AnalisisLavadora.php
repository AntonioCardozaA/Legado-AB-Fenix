<?php

namespace App\Models;

use App\Models\Concerns\UppercasesActividad;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AnalisisLavadora extends Model
{
    use HasFactory, UppercasesActividad;

    public const TIPO_EQUIPO = 'lavadora';
    public const ESTADO_BUENO = 'Buen estado';
    public const ESTADO_REQUIERE_REVISION = 'Requiere revisión';
    public const ESTADOS_DESGASTE = ['Desgaste moderado', 'Desgaste severo'];
    public const ESTADO_DANADO = 'Dañado - Requiere cambio';
    public const ESTADO_CAMBIADO = 'Cambiado';
    public const ESTADOS = [
        self::ESTADO_BUENO,
        self::ESTADO_REQUIERE_REVISION,
        'Desgaste moderado',
        'Desgaste severo',
        self::ESTADO_DANADO,
        self::ESTADO_CAMBIADO,
    ];

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
        'tipo_equipo',
        'maquina',
    ];

    protected $casts = [
        'evidencia_fotos' => 'array',
        'fecha_analisis' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tipo_equipo_lavadora', function (Builder $query): void {
            $query->where(function (Builder $query): void {
                $query->where($query->getModel()->qualifyColumn('tipo_equipo'), self::TIPO_EQUIPO)
                    ->orWhereNull($query->getModel()->qualifyColumn('tipo_equipo'));
            });
        });

        static::creating(function (self $analisis): void {
            $analisis->tipo_equipo ??= self::TIPO_EQUIPO;
        });
    }

    public static function getEstadoOpciones(): array
    {
        return [
            self::ESTADO_BUENO => '✅ Buen estado',
            self::ESTADO_REQUIERE_REVISION => '🔧 Requiere revisión',
            'Desgaste moderado' => '⚠️ Desgaste moderado',
            'Desgaste severo' => '⚠️ Desgaste severo',
            self::ESTADO_DANADO => '❌ Dañado - Requiere cambio',
            self::ESTADO_CAMBIADO => '🔄 Cambiado',
        ];
    }

    public static function esEstadoBueno(?string $estado): bool
    {
        return $estado === self::ESTADO_BUENO;
    }

    public static function esEstadoRequiereRevision(?string $estado): bool
    {
        return $estado === self::ESTADO_REQUIERE_REVISION;
    }

    public static function esEstadoDesgaste(?string $estado): bool
    {
        return in_array($estado, self::ESTADOS_DESGASTE, true);
    }

    public static function esEstadoDanado(?string $estado): bool
    {
        return $estado === self::ESTADO_DANADO;
    }

    public static function esEstadoCambiado(?string $estado): bool
    {
        return $estado === self::ESTADO_CAMBIADO;
    }

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
        $latestIds = DB::table('analisis_componentes as actual')
            ->leftJoin('analisis_componentes as mas_reciente', function ($join) {
                $join->on('actual.linea_id', '=', 'mas_reciente.linea_id')
                    ->on('actual.componente_id', '=', 'mas_reciente.componente_id')
                    ->on('actual.reductor', '=', 'mas_reciente.reductor')
                    ->whereRaw("COALESCE(actual.lado, '') = COALESCE(mas_reciente.lado, '')")
                    ->where(function ($query) {
                        $query->where('mas_reciente.tipo_equipo', self::TIPO_EQUIPO)
                            ->orWhereNull('mas_reciente.tipo_equipo');
                    })
                    ->where(function ($subQuery) {
                        $subQuery->whereColumn('mas_reciente.fecha_analisis', '>', 'actual.fecha_analisis')
                            ->orWhere(function ($tieBreaker) {
                                $tieBreaker->whereColumn('mas_reciente.fecha_analisis', '=', 'actual.fecha_analisis')
                                    ->whereColumn('mas_reciente.id', '>', 'actual.id');
                            });
                    });
            })
            ->where(function ($query) {
                $query->where('actual.tipo_equipo', self::TIPO_EQUIPO)
                    ->orWhereNull('actual.tipo_equipo');
            })
            ->whereNull('mas_reciente.id')
            ->select('actual.id');

        return $query->whereIn($this->qualifyColumn('id'), $latestIds);
    }

    public function planAccion(): HasMany
    {
        return $this->hasMany(PlanAccion::class, 'linea_id', 'linea_id');
    }

    public function getNombreCompletoAttribute()
    {
        return 'Línea ' . $this->linea_id;
    }

    public function getActividadesPendientesAttribute()
    {
        return $this->planAccion()
                    ->where(function ($query) {
                        $query->where('tipo_equipo', 'lavadora')
                              ->orWhereNull('tipo_equipo');
                    })
                    ->where('completado', false)
                    ->count();
    }

    public function getProximasActividadesAttribute()
    {
        return $this->planAccion()
                    ->where(function ($query) {
                        $query->where('tipo_equipo', 'lavadora')
                              ->orWhereNull('tipo_equipo');
                    })
                    ->where('completado', false)
                    ->where(function ($query) {
                        $query->whereDate('fecha_pcm1', '>=', now())
                              ->orWhereDate('fecha_pcm2', '>=', now())
                              ->orWhereDate('fecha_pcm3', '>=', now())
                              ->orWhereDate('fecha_pcm4', '>=', now());
                    })
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

    public function cambiosFecha(): HasMany
    {
        return $this->hasMany(AnalisisLavadoraFechaCambio::class, 'analisis_lavadora_id');
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(LavadoraCostEntry::class, 'analisis_lavadora_id');
    }

    public function costRuleExclusions(): HasMany
    {
        return $this->hasMany(LavadoraCostRuleExclusion::class, 'analisis_lavadora_id');
    }
}
