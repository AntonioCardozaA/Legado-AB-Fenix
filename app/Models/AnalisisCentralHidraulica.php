<?php

namespace App\Models;

use App\Models\Concerns\UppercasesActividad;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnalisisCentralHidraulica extends Model
{
    use HasFactory, SoftDeletes, UppercasesActividad;

    public const TIPO_REGISTRO_QUICK = 'quick';
    public const TIPO_REGISTRO_NORMAL = 'normal';
    public const TIPOS_REGISTRO = [
        self::TIPO_REGISTRO_QUICK,
        self::TIPO_REGISTRO_NORMAL,
    ];

    public const LADO_1 = 'LADO_1';
    public const LADO_2 = 'LADO_2';
    public const LADOS = [
        self::LADO_1 => 'Lado 1',
        self::LADO_2 => 'Lado 2',
    ];

    public const ESTADO_BUENO = AnalisisPasteurizadora::ESTADO_BUENO;
    public const ESTADO_REQUIERE_REVISION = AnalisisPasteurizadora::ESTADO_REQUIERE_REVISION;
    public const ESTADOS_DESGASTE = AnalisisPasteurizadora::ESTADOS_DESGASTE;
    public const ESTADO_DANADO = AnalisisPasteurizadora::ESTADO_DANADO;
    public const ESTADO_CAMBIADO = AnalisisPasteurizadora::ESTADO_CAMBIADO;
    public const ESTADOS = AnalisisPasteurizadora::ESTADOS;

    protected $table = 'analisis_central_hidraulica';

    protected $fillable = [
        'linea_id',
        'configuracion_id',
        'componente_id',
        'piso',
        'lado',
        'fecha_inicio',
        'fecha_fin',
        'fecha_analisis',
        'numero_orden',
        'estado',
        'actividad',
        'responsable',
        'usuario_id',
        'observaciones',
        'evidencia_fotos',
        'cantidad_componentes_revisados',
        'total_componentes',
        'componentes_revisados',
        'tipo_registro',
        'resuelto_por_cambio',
        'fecha_resolucion',
        'nota_resolucion',
        'id_registro_que_resolvio',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_analisis' => 'date',
        'evidencia_fotos' => 'array',
        'cantidad_componentes_revisados' => 'integer',
        'total_componentes' => 'integer',
        'componentes_revisados' => 'array',
        'resuelto_por_cambio' => 'boolean',
        'fecha_resolucion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $analisis): void {
            $analisis->tipo_registro = self::normalizarTipoRegistro($analisis->tipo_registro);
            $analisis->piso = CentralHidraulicaConfiguracion::normalizarPiso($analisis->piso)
                ?: CentralHidraulicaConfiguracion::PISO_SUPERIOR;
            $analisis->lado = self::normalizarLado($analisis->lado);
            $analisis->estado = self::normalizarEstado($analisis->estado);
        });

        static::updating(function (self $analisis): void {
            $analisis->tipo_registro = self::normalizarTipoRegistro($analisis->tipo_registro);
            $analisis->piso = CentralHidraulicaConfiguracion::normalizarPiso($analisis->piso)
                ?: CentralHidraulicaConfiguracion::PISO_SUPERIOR;
            $analisis->lado = self::normalizarLado($analisis->lado);
            $analisis->estado = self::normalizarEstado($analisis->estado);
        });
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }

    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(CentralHidraulicaConfiguracion::class, 'configuracion_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(CentralHidraulicaComponente::class, 'componente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function registroResolutor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_registro_que_resolvio');
    }

    public function scopeActivos($query)
    {
        return $query->where('resuelto_por_cambio', false);
    }

    public function scopeQuick($query)
    {
        return $query->where(function ($subQuery) {
            $subQuery->where('tipo_registro', self::TIPO_REGISTRO_QUICK)
                ->orWhereNull('tipo_registro');
        });
    }

    public function scopeNormal($query)
    {
        return $query->where('tipo_registro', self::TIPO_REGISTRO_NORMAL);
    }

    public static function getEstadoOpciones(): array
    {
        return AnalisisPasteurizadora::getEstadoOpciones();
    }

    public static function normalizarEstado(?string $estado): string
    {
        return AnalisisPasteurizadora::normalizarEstado($estado);
    }

    public static function esEstadoBueno(?string $estado): bool
    {
        return AnalisisPasteurizadora::esEstadoBueno($estado);
    }

    public static function esEstadoRequiereRevision(?string $estado): bool
    {
        return AnalisisPasteurizadora::esEstadoRequiereRevision($estado);
    }

    public static function esEstadoDesgaste(?string $estado): bool
    {
        return AnalisisPasteurizadora::esEstadoDesgaste($estado);
    }

    public static function esEstadoDanado(?string $estado): bool
    {
        return AnalisisPasteurizadora::esEstadoDanado($estado);
    }

    public static function esEstadoCambiado(?string $estado): bool
    {
        return AnalisisPasteurizadora::esEstadoCambiado($estado);
    }

    public static function normalizarTipoRegistro(?string $tipoRegistro): string
    {
        return in_array($tipoRegistro, self::TIPOS_REGISTRO, true)
            ? $tipoRegistro
            : self::TIPO_REGISTRO_NORMAL;
    }

    public static function normalizarLado(?string $lado): ?string
    {
        $lado = strtoupper(trim((string) $lado));

        return match ($lado) {
            self::LADO_1, 'LADO 1', '1' => self::LADO_1,
            self::LADO_2, 'LADO 2', '2' => self::LADO_2,
            default => null,
        };
    }

    public static function ladoLabel(?string $lado): string
    {
        $lado = self::normalizarLado($lado);

        return $lado ? self::LADOS[$lado] : 'Sin lado';
    }

    public static function normalizarComponentesRevisados($value, ?int $totalComponentes = null): array
    {
        $componentes = $value;

        if (is_string($componentes) && trim($componentes) !== '') {
            $decoded = json_decode($componentes, true);
            $componentes = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($componentes)) {
            return [];
        }

        return collect($componentes)
            ->map(fn ($item) => is_numeric($item) ? (int) $item : null)
            ->filter(fn ($item) => $item !== null && $item > 0 && ($totalComponentes === null || $item <= $totalComponentes))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function buildResumenCiclo($registros, int $totalComponentes): array
    {
        $totalComponentes = max(1, $totalComponentes);
        $registros = collect($registros)
            ->filter(fn ($registro) => $registro instanceof self)
            ->sortBy(fn (self $registro) => $registro->cycleSortKey())
            ->values();

        $ciclos = [];
        $registrosCiclo = collect();
        $componentesCiclo = collect();

        foreach ($registros as $registro) {
            $registrosCiclo->push($registro);
            $componentesCiclo = $componentesCiclo
                ->merge($registro->componentesRevisadosParaTotal($totalComponentes))
                ->map(fn ($pieza) => (int) $pieza)
                ->filter(fn (int $pieza) => $pieza > 0 && $pieza <= $totalComponentes)
                ->unique()
                ->sort()
                ->values();

            if ($componentesCiclo->count() >= $totalComponentes) {
                $ciclos[] = [
                    'registros' => $registrosCiclo->values(),
                    'resumen' => self::resumenComponentesCiclo($componentesCiclo, $totalComponentes),
                    'completado' => true,
                ];

                $registrosCiclo = collect();
                $componentesCiclo = collect();
            }
        }

        if ($registrosCiclo->isNotEmpty()) {
            $ciclos[] = [
                'registros' => $registrosCiclo->values(),
                'resumen' => self::resumenComponentesCiclo($componentesCiclo, $totalComponentes),
                'completado' => $componentesCiclo->count() >= $totalComponentes,
            ];
        }

        $cicloActivo = null;
        $ultimoCicloCompletado = null;

        foreach ($ciclos as $ciclo) {
            if ($ciclo['completado']) {
                $ultimoCicloCompletado = $ciclo;
                continue;
            }

            $cicloActivo = $ciclo;
        }

        $cicloVisible = $cicloActivo ?: $ultimoCicloCompletado;
        $resumenVacio = self::resumenComponentesCiclo([], $totalComponentes);

        return [
            'ciclos' => $ciclos,
            'ciclo_actual' => $cicloActivo,
            'ultimo_ciclo_completado' => $ultimoCicloCompletado,
            'registros_actuales' => collect($cicloActivo['registros'] ?? []),
            'registros_visibles' => collect($cicloVisible['registros'] ?? []),
            'resumen_actual' => $cicloActivo['resumen'] ?? $resumenVacio,
            'resumen_visible' => $cicloVisible['resumen'] ?? $resumenVacio,
            'tiene_ciclo_activo' => $cicloActivo !== null,
            'tiene_ciclo_completado' => $ultimoCicloCompletado !== null,
            'total_componentes' => $totalComponentes,
        ];
    }

    public static function getPiezasDisponiblesParaRegistro(
        int|string $lineaId,
        int|string $configuracionId,
        ?string $lado,
        ?int $excludeId = null,
        ?int $totalComponentes = null
    ): array {
        $configuracion = CentralHidraulicaConfiguracion::find($configuracionId);
        $totalComponentes = $totalComponentes ?: (int) ($configuracion?->cantidad ?? 0);

        if ($totalComponentes <= 0) {
            return [];
        }

        $query = self::query()
            ->where('linea_id', $lineaId)
            ->where('configuracion_id', $configuracionId)
            ->when($lado, fn ($query) => $query->where('lado', self::normalizarLado($lado)))
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->orderBy('id');

        $resumen = self::buildResumenCiclo($query->get(), $totalComponentes);

        if ($resumen['tiene_ciclo_activo']) {
            return $resumen['resumen_actual']['pendientes'];
        }

        return range(1, $totalComponentes);
    }

    public function getComponenteNombreAttribute(): string
    {
        return $this->componente?->nombre_display ?? 'Componente sin catalogo';
    }

    public function getEsContabilizableAttribute(): bool
    {
        if ($this->relationLoaded('configuracion')) {
            return (bool) ($this->configuracion?->es_contabilizable ?? true);
        }

        if ($this->relationLoaded('componente')) {
            if ($this->componente?->codigo === CentralHidraulicaConfiguracion::CODIGO_ACEITE) {
                return false;
            }

            return (bool) ($this->componente?->contabilizable ?? true);
        }

        return $this->componente()
            ->value('codigo') !== CentralHidraulicaConfiguracion::CODIGO_ACEITE;
    }

    public function getCantidadDisplayAttribute(): string
    {
        if (!$this->es_contabilizable) {
            $cantidad = (int) (
                $this->cantidad_componentes_revisados
                ?: $this->total_componentes
                ?: $this->configuracion?->cantidad
                ?: CentralHidraulicaConfiguracion::ACEITE_LITROS_DEFAULT
            );
            $unidad = $this->configuracion?->unidad ?: $this->componente?->unidad ?: 'lts';

            return trim($cantidad . ' ' . $unidad);
        }

        return $this->total_componentes
            ? $this->cantidad_componentes_revisados . '/' . $this->total_componentes
            : (string) $this->cantidad_componentes_revisados;
    }

    public function getPisoLabelAttribute(): string
    {
        return CentralHidraulicaConfiguracion::pisoLabel($this->piso);
    }

    public function getLadoLabelAttribute(): string
    {
        return self::ladoLabel($this->lado);
    }

    public function getTipoRegistroAttribute($value): string
    {
        return self::normalizarTipoRegistro($value);
    }

    public function getTipoRegistroLabelAttribute(): string
    {
        return $this->tipo_registro === self::TIPO_REGISTRO_QUICK
            ? 'Revision de seguimiento'
            : 'Revision programada';
    }

    public function getEsRegistroQuickAttribute(): bool
    {
        return $this->tipo_registro === self::TIPO_REGISTRO_QUICK;
    }

    public function getEsRegistroNormalAttribute(): bool
    {
        return $this->tipo_registro === self::TIPO_REGISTRO_NORMAL;
    }

    public function getComponentesRevisadosListaAttribute(): array
    {
        return self::normalizarComponentesRevisados($this->componentes_revisados, $this->total_componentes);
    }

    public function getPorcentajeAvanceAttribute(): ?int
    {
        $total = (int) ($this->total_componentes ?? 0);

        if ($total <= 0) {
            return null;
        }

        return (int) round((min($this->cantidad_componentes_revisados, $total) / $total) * 100);
    }

    public function getEstadoBadgeAttribute(): array
    {
        if (self::esEstadoDanado($this->estado)) {
            return ['class' => 'bg-red-50 text-red-700 border-red-200', 'icon' => 'fa-circle-exclamation'];
        }

        return match ($this->estado) {
            self::ESTADO_BUENO => ['class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-circle-check'],
            self::ESTADO_REQUIERE_REVISION => ['class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'fa-screwdriver-wrench'],
            'Desgaste moderado', 'Desgaste severo' => ['class' => 'bg-orange-50 text-orange-700 border-orange-200', 'icon' => 'fa-triangle-exclamation'],
            self::ESTADO_CAMBIADO => ['class' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'fa-arrows-rotate'],
            default => ['class' => 'bg-gray-50 text-gray-700 border-gray-200', 'icon' => 'fa-circle-question'],
        };
    }

    public function setComponentesRevisadosAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['componentes_revisados'] = null;
            return;
        }

        $componentes = self::normalizarComponentesRevisados($value, $this->attributes['total_componentes'] ?? null);

        $this->attributes['componentes_revisados'] = json_encode($componentes);
        $this->attributes['cantidad_componentes_revisados'] = count($componentes);
    }

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

        $decoded = json_decode((string) $value, true);
        $this->attributes['evidencia_fotos'] = json_encode(is_array($decoded) ? array_values($decoded) : [$value]);
    }

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = self::normalizarEstado($value);
    }

    public function marcarComoResuelto(self $registroResolutor, ?string $nota = null): void
    {
        $numeroOrden = $registroResolutor->numero_orden ?: 'sin numero de orden';

        $this->update([
            'resuelto_por_cambio' => true,
            'fecha_resolucion' => now(),
            'id_registro_que_resolvio' => $registroResolutor->id,
            'nota_resolucion' => $nota ?: "Resuelto por orden #{$numeroOrden}",
        ]);
    }

    private function componentesRevisadosParaTotal(int $totalComponentes): array
    {
        $componentes = self::normalizarComponentesRevisados($this->componentes_revisados, $totalComponentes);

        if (!empty($componentes)) {
            return $componentes;
        }

        $cantidad = min((int) ($this->cantidad_componentes_revisados ?? 0), $totalComponentes);

        return $cantidad > 0 ? range(1, $cantidad) : [];
    }

    private function cycleSortKey(): string
    {
        $fechaAnalisis = $this->fecha_analisis?->format('Ymd') ?? '00000000';
        $createdAt = str_pad((string) ($this->created_at?->timestamp ?? 0), 12, '0', STR_PAD_LEFT);
        $id = str_pad((string) ($this->id ?? 0), 10, '0', STR_PAD_LEFT);

        return $fechaAnalisis . '-' . $createdAt . '-' . $id;
    }

    private static function resumenComponentesCiclo($componentes, int $totalComponentes): array
    {
        $revisados = collect($componentes)
            ->map(fn ($pieza) => (int) $pieza)
            ->filter(fn (int $pieza) => $pieza > 0 && $pieza <= $totalComponentes)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'revisados' => $revisados,
            'pendientes' => array_values(array_diff(range(1, $totalComponentes), $revisados)),
            'cantidad_revisada' => count($revisados),
            'total' => $totalComponentes,
            'porcentaje' => $totalComponentes > 0 ? (int) round((count($revisados) / $totalComponentes) * 100) : 0,
        ];
    }
}
