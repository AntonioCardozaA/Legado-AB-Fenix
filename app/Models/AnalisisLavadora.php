<?php

namespace App\Models;

use App\Models\Concerns\UppercasesActividad;
use App\Support\LavadoraCatalog;
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
    public const COMPONENTE_CODIGOS_BASE = LavadoraCatalog::COMPONENTE_CODIGOS_BASE;
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
    public const CORRECCION_PENDIENTE = 'pendiente_corregir';
    public const CORRECCION_CORREGIDO = 'corregido_buen_estado';
    public const CORRECCION_COMPONENTE_CAMBIADO = 'componente_cambiado';
    public const ESTADOS_CORRECCION = [
        self::CORRECCION_PENDIENTE,
        self::CORRECCION_CORREGIDO,
        self::CORRECCION_COMPONENTE_CAMBIADO,
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
        'estado_correccion',
        'fecha_correccion',
        'corregido_por',
        'observaciones_reparacion',
        'evidencias_reparacion',
        'tipo_intervencion',
        'componente_instalado',
        'numero_parte',
        'proveedor',
        'garantia',
        'fecha_cambio',
        'costo_refacciones',
        'costo_mano_obra',
        'costo_servicios_externos',
        'costo_total_intervencion',
        'tiempo_reparacion_horas',
        'responsable_trabajo',
        'comentarios_costos',
        'actividad',
        'usuario_id',
        'evidencia_fotos',
        'tipo_equipo',
        'maquina',
    ];

    protected $casts = [
        'evidencia_fotos' => 'array',
        'evidencias_reparacion' => 'array',
        'fecha_analisis' => 'date',
        'fecha_correccion' => 'datetime',
        'fecha_cambio' => 'date',
        'costo_refacciones' => 'float',
        'costo_mano_obra' => 'float',
        'costo_servicios_externos' => 'float',
        'costo_total_intervencion' => 'float',
        'tiempo_reparacion_horas' => 'float',
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

    public static function getEstadoCorreccionOpciones(): array
    {
        return [
            self::CORRECCION_PENDIENTE => 'Pendiente de corregir',
            self::CORRECCION_CORREGIDO => 'Corregido (Buen Estado)',
            self::CORRECCION_COMPONENTE_CAMBIADO => 'Componente Cambiado',
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

    public static function requiereCierreAdministrativo(?string $estado): bool
    {
        return self::esEstadoDanado($estado)
            || self::esEstadoDesgaste($estado)
            || self::esEstadoRequiereRevision($estado);
    }

    public function getEstadoCorreccionAttribute($value): string
    {
        return $value ?: self::CORRECCION_PENDIENTE;
    }

    public function getEstadoCorreccionLabelAttribute(): string
    {
        return self::getEstadoCorreccionOpciones()[$this->estado_correccion] ?? 'Pendiente de corregir';
    }

    public function getEstadoOperativoAttribute(): string
    {
        return match ($this->estado_correccion) {
            self::CORRECCION_CORREGIDO => self::ESTADO_BUENO,
            self::CORRECCION_COMPONENTE_CAMBIADO => self::ESTADO_CAMBIADO,
            default => $this->estado ?: self::ESTADO_BUENO,
        };
    }

    public function getEstadoOperativoLabelAttribute(): string
    {
        if ($this->estado_operativo === $this->estado) {
            return $this->estado_operativo;
        }

        return $this->estado_operativo . ' (por cierre administrativo)';
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

    public function setReductorAttribute($value): void
    {
        $this->attributes['reductor'] = LavadoraCatalog::normalizarReductor($value) ?? $value;
    }

    public function getEvidenciasReparacionAttribute($value): array
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

    public function setEvidenciasReparacionAttribute($value): void
    {
        if (is_null($value) || $value === '') {
            $this->attributes['evidencias_reparacion'] = json_encode([]);
            return;
        }

        if (is_array($value)) {
            $this->attributes['evidencias_reparacion'] = json_encode(array_values($value));
            return;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            $this->attributes['evidencias_reparacion'] = json_encode(array_values($decoded));
            return;
        }

        $this->attributes['evidencias_reparacion'] = json_encode([$value]);
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

    public function usuarioCorreccion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corregido_por');
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

    public function scopeOrdenVigente(Builder $query): Builder
    {
        return $query
            ->orderByRaw("COALESCE(" . $this->qualifyColumn('fecha_analisis') . ", " . $this->qualifyColumn('created_at') . ", '1000-01-01') DESC")
            ->orderByDesc($this->qualifyColumn('id'));
    }

    public function scopeEstadoOperativo(Builder $query, mixed $estado): Builder
    {
        if (!$estado) {
            return $query;
        }

        $estadoCorreccion = $this->qualifyColumn('estado_correccion');
        $estadoOriginal = $this->qualifyColumn('estado');

        return $query->whereRaw(
            "CASE
                WHEN COALESCE({$estadoCorreccion}, ?) = ? THEN ?
                WHEN COALESCE({$estadoCorreccion}, ?) = ? THEN ?
                ELSE {$estadoOriginal}
            END = ?",
            [
                self::CORRECCION_PENDIENTE,
                self::CORRECCION_CORREGIDO,
                self::ESTADO_BUENO,
                self::CORRECCION_PENDIENTE,
                self::CORRECCION_COMPONENTE_CAMBIADO,
                self::ESTADO_CAMBIADO,
                $estado,
            ]
        );
    }

    public function scopeUltimosPorComponente(Builder $query): Builder
    {
        $fechaActual = "COALESCE(actual.fecha_analisis, actual.created_at, '1000-01-01')";
        $fechaMasReciente = "COALESCE(mas_reciente.fecha_analisis, mas_reciente.created_at, '1000-01-01')";
        $codigoActual = self::sqlCodigoBaseComponente('componente_actual');
        $codigoMasReciente = self::sqlCodigoBaseComponente('componente_mas_reciente');

        $latestIds = DB::table('analisis_componentes as actual')
            ->leftJoin('componentes as componente_actual', 'actual.componente_id', '=', 'componente_actual.id')
            ->whereNotExists(function ($subQuery) use ($fechaActual, $fechaMasReciente, $codigoActual, $codigoMasReciente): void {
                $subQuery->selectRaw('1')
                    ->from('analisis_componentes as mas_reciente')
                    ->leftJoin('componentes as componente_mas_reciente', 'mas_reciente.componente_id', '=', 'componente_mas_reciente.id')
                    ->whereColumn('mas_reciente.linea_id', 'actual.linea_id')
                    ->whereRaw("COALESCE(mas_reciente.reductor, '') = COALESCE(actual.reductor, '')")
                    ->whereRaw("COALESCE(mas_reciente.lado, '') = COALESCE(actual.lado, '')")
                    ->whereRaw($codigoMasReciente . ' = ' . $codigoActual)
                    ->where(function ($query) {
                        $query->where('mas_reciente.tipo_equipo', self::TIPO_EQUIPO)
                            ->orWhereNull('mas_reciente.tipo_equipo');
                    })
                    ->where(function ($dateQuery) use ($fechaActual, $fechaMasReciente) {
                        $dateQuery->whereRaw($fechaMasReciente . ' > ' . $fechaActual)
                            ->orWhere(function ($tieBreaker) use ($fechaActual, $fechaMasReciente) {
                                $tieBreaker->whereRaw($fechaMasReciente . ' = ' . $fechaActual)
                                    ->whereColumn('mas_reciente.id', '>', 'actual.id');
                            });
                    });
            })
            ->where(function ($query) {
                $query->where('actual.tipo_equipo', self::TIPO_EQUIPO)
                    ->orWhereNull('actual.tipo_equipo');
            })
            ->select('actual.id');

        return $query->whereIn($this->qualifyColumn('id'), $latestIds);
    }

    public static function codigoBaseComponente(?string $codigo): string
    {
        $codigo = strtoupper(trim((string) $codigo));
        $codigoNormalizado = self::normalizarCodigoBusqueda($codigo);
        $codigosBase = self::COMPONENTE_CODIGOS_BASE;

        usort($codigosBase, fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        foreach ($codigosBase as $codigoBase) {
            $codigoBaseNormalizado = self::normalizarCodigoBusqueda($codigoBase);
            if (
                $codigoNormalizado === $codigoBaseNormalizado
                || str_contains('_' . $codigoNormalizado . '_', '_' . $codigoBaseNormalizado . '_')
                || str_contains($codigoNormalizado, $codigoBaseNormalizado)
            ) {
                return $codigoBase;
            }

            foreach (self::aliasCodigoBase($codigoBase) as $alias) {
                $aliasNormalizado = self::normalizarCodigoBusqueda($alias);

                if (
                    $codigoNormalizado === $aliasNormalizado
                    || str_contains('_' . $codigoNormalizado . '_', '_' . $aliasNormalizado . '_')
                    || str_contains($codigoNormalizado, $aliasNormalizado)
                ) {
                    return $codigoBase;
                }
            }
        }

        return $codigoNormalizado ?: $codigo;
    }

    public function getFechaRevisionHistoricoAttribute(): ?Carbon
    {
        if ($this->fecha_analisis) {
            return Carbon::parse($this->fecha_analisis)->startOfDay();
        }

        return $this->created_at
            ? Carbon::parse($this->created_at)->startOfDay()
            : null;
    }

    public function getCodigoBaseComponenteAttribute(): string
    {
        return self::codigoBaseComponente($this->componente?->codigo);
    }

    private static function sqlCodigoBaseComponente(string $alias): string
    {
        $codigo = 'UPPER(COALESCE(' . $alias . ".codigo, ''))";
        $codigoNormalizado = "REPLACE(REPLACE(REPLACE({$codigo}, '-', '_'), ' ', '_'), '__', '_')";
        $cases = [];

        foreach (self::COMPONENTE_CODIGOS_BASE as $codigoBase) {
            $conditions = [
                "{$codigo} = '{$codigoBase}'",
                "{$codigo} LIKE '%{$codigoBase}%'",
                "{$codigoNormalizado} LIKE '%{$codigoBase}%'",
            ];

            foreach (self::aliasCodigoBase($codigoBase) as $aliasCodigo) {
                $aliasNormalizado = self::normalizarCodigoBusqueda($aliasCodigo);
                $conditions[] = "{$codigoNormalizado} LIKE '%{$aliasNormalizado}%'";
            }

            $cases[] = 'WHEN ' . implode(' OR ', $conditions) . " THEN '{$codigoBase}'";
        }

        return 'CASE ' . implode(' ', $cases) . ' ELSE ' . $codigo . ' END';
    }

    private static function normalizarCodigoBusqueda(?string $codigo): string
    {
        $codigo = strtoupper(trim((string) $codigo));
        $codigo = preg_replace('/[^A-Z0-9]+/', '_', $codigo) ?? '';
        $codigo = preg_replace('/_+/', '_', $codigo) ?? '';

        return trim($codigo, '_');
    }

    /**
     * @return array<int, string>
     */
    private static function aliasCodigoBase(string $codigoBase): array
    {
        return match ($codigoBase) {
            'CATARINAS' => ['CATARINA'],
            'GUI_INF_TANQUE' => ['GUIA_INF', 'GUIA_INFERIOR', 'GUIA_TANQUE_INFERIOR'],
            'GUI_INT_TANQUE' => ['GUIA_INT', 'GUIA_INTERMEDIA', 'GUIA_TANQUE_INTERMEDIA', 'GUI_INT_TAQNQUE'],
            'GUI_SUP_TANQUE' => ['GUIA_SUP', 'GUIA_SUPERIOR', 'GUIA_TANQUE_SUPERIOR'],
            'BUJE_ESPIGA' => ['BUE_BAQ', 'BUJE_BAQUELITA', 'ESPIGA_FLECHA'],
            'RV200_SIN_FIN' => ['SIN_FIN_CORONA_RV200', 'RV200_SINFIN', 'RV200_SIN_FIN_CORONA'],
            default => [],
        };
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
