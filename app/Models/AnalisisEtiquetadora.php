<?php

namespace App\Models;

use App\Models\Concerns\UppercasesActividad;
use App\Support\EtiquetadoraCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AnalisisEtiquetadora extends Model
{
    use HasFactory, UppercasesActividad;

    public const TIPO_EQUIPO = EtiquetadoraCatalog::TIPO_EQUIPO;
    public const MAQUINAS = ['A', 'B', 'C'];
    public const ESTADO_BUENO = AnalisisLavadora::ESTADO_BUENO;
    public const ESTADO_REQUIERE_REVISION = AnalisisLavadora::ESTADO_REQUIERE_REVISION;
    public const ESTADOS_DESGASTE = AnalisisLavadora::ESTADOS_DESGASTE;
    public const ESTADO_DANADO = AnalisisLavadora::ESTADO_DANADO;
    public const ESTADO_CAMBIADO = AnalisisLavadora::ESTADO_CAMBIADO;
    public const ESTADOS = AnalisisLavadora::ESTADOS;

    protected $table = 'analisis_etiquetadora';

    protected $fillable = [
        'linea_id',
        'componente_id',
        'reductor',
        'maquina',
        'lado',
        'fecha_analisis',
        'numero_orden',
        'estado',
        'actividad',
        'usuario_id',
        'evidencia_fotos',
        'total_componentes',
        'cantidad_componentes_revisados',
        'componentes_revisados',
        'categoria_id',
        'numero_r_id',
    ];

    protected $casts = [
        'evidencia_fotos' => 'array',
        'componentes_revisados' => 'array',
        'total_componentes' => 'integer',
        'cantidad_componentes_revisados' => 'integer',
        'fecha_analisis' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $analisis): void {
            if ($analisis->maquina) {
                $analisis->maquina = strtoupper(trim((string) $analisis->maquina));
            }

            if ($analisis->maquina && blank($analisis->reductor)) {
                $analisis->reductor = EtiquetadoraCatalog::maquinaLabel($analisis->maquina);
            }
        });
    }

    public static function getEstadoOpciones(): array
    {
        return AnalisisLavadora::getEstadoOpciones();
    }

    public static function estados(): array
    {
        return AnalisisLavadora::ESTADOS;
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

    public static function buildResumenCicloPiezas($registros, int $totalComponentes): array
    {
        $totalComponentes = max(1, $totalComponentes);
        $registros = collect($registros)
            ->filter(fn ($registro) => $registro instanceof self)
            ->sortBy(fn (self $registro) => self::cycleSortKey($registro))
            ->values();

        $ciclos = [];
        $registrosCiclo = collect();
        $piezasCiclo = collect();

        foreach ($registros as $registro) {
            $registrosCiclo->push($registro);
            $piezasCiclo = $piezasCiclo
                ->merge($registro->piezasRevisadasParaTotal($totalComponentes))
                ->map(fn ($pieza) => (int) $pieza)
                ->filter(fn (int $pieza) => $pieza > 0 && $pieza <= $totalComponentes)
                ->unique()
                ->sort()
                ->values();

            if ($piezasCiclo->count() >= $totalComponentes) {
                $ciclos[] = [
                    'registros' => $registrosCiclo->values(),
                    'resumen' => self::resumenPiezasCiclo($piezasCiclo, $totalComponentes),
                    'completado' => true,
                ];

                $registrosCiclo = collect();
                $piezasCiclo = collect();
            }
        }

        if ($registrosCiclo->isNotEmpty()) {
            $ciclos[] = [
                'registros' => $registrosCiclo->values(),
                'resumen' => self::resumenPiezasCiclo($piezasCiclo, $totalComponentes),
                'completado' => $piezasCiclo->count() >= $totalComponentes,
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
        $resumenVacio = self::resumenPiezasCiclo([], $totalComponentes);

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

    public static function getResumenCicloComponente(
        int|string $lineaId,
        int|string $componenteId,
        ?string $maquina,
        ?int $excludeId = null,
        ?int $totalComponentes = null,
        bool $lockForUpdate = false
    ): array {
        $maquina = strtoupper(trim((string) $maquina));
        $totalComponentes = $totalComponentes ?: (int) (Componente::find($componenteId)?->cantidad_total ?? 1);

        $registros = self::query()
            ->where('linea_id', $lineaId)
            ->where('componente_id', $componenteId)
            ->where('maquina', $maquina)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return self::buildResumenCicloPiezas($registros, max(1, (int) $totalComponentes));
    }

    public static function getPiezasDisponiblesParaRegistro(
        int|string $lineaId,
        int|string $componenteId,
        ?string $maquina,
        ?int $excludeId = null,
        ?int $totalComponentes = null,
        bool $lockForUpdate = false
    ): array {
        $totalComponentes = max(1, (int) ($totalComponentes ?: (Componente::find($componenteId)?->cantidad_total ?? 1)));
        $resumen = self::getResumenCicloComponente(
            $lineaId,
            $componenteId,
            $maquina,
            $excludeId,
            $totalComponentes,
            $lockForUpdate
        );

        if ($resumen['tiene_ciclo_activo']) {
            return $resumen['resumen_actual']['piezas_pendientes'];
        }

        return range(1, $totalComponentes);
    }

    public static function getPiezasBloqueadasCicloActual(
        int|string $lineaId,
        int|string $componenteId,
        ?string $maquina,
        ?int $excludeId = null,
        ?int $totalComponentes = null
    ): array {
        $resumen = self::getResumenCicloComponente($lineaId, $componenteId, $maquina, $excludeId, $totalComponentes);

        if (!$resumen['tiene_ciclo_activo']) {
            return [];
        }

        return $resumen['resumen_actual']['piezas_revisadas'];
    }

    private static function resumenPiezasCiclo($piezasRevisadas, int $totalComponentes): array
    {
        $totalComponentes = max(1, $totalComponentes);
        $piezasRevisadas = collect($piezasRevisadas)
            ->map(fn ($pieza) => (int) $pieza)
            ->filter(fn (int $pieza) => $pieza > 0 && $pieza <= $totalComponentes)
            ->unique()
            ->sort()
            ->values();
        $piezasPendientes = collect(range(1, $totalComponentes))
            ->diff($piezasRevisadas)
            ->values();

        return [
            'total_componentes' => $totalComponentes,
            'cantidad_revisada' => $piezasRevisadas->count(),
            'cantidad_pendiente' => $piezasPendientes->count(),
            'piezas_revisadas' => $piezasRevisadas->all(),
            'piezas_pendientes' => $piezasPendientes->all(),
            'completado' => $piezasRevisadas->count() >= $totalComponentes,
        ];
    }

    private static function cycleSortKey(self $registro): string
    {
        $fechaAnalisis = $registro->fecha_analisis?->format('Ymd') ?? '00000000';
        $createdAt = str_pad((string) ($registro->created_at?->timestamp ?? 0), 12, '0', STR_PAD_LEFT);
        $id = str_pad((string) ($registro->id ?? 0), 10, '0', STR_PAD_LEFT);

        return $fechaAnalisis . '-' . $createdAt . '-' . $id;
    }

    public static function esEstadoBueno(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoBueno($estado);
    }

    public static function esEstadoRequiereRevision(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoRequiereRevision($estado);
    }

    public static function esEstadoDesgaste(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoDesgaste($estado);
    }

    public static function esEstadoDanado(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoDanado($estado);
    }

    public static function esEstadoCambiado(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoCambiado($estado);
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

    public function getComponentesRevisadosListaAttribute(): array
    {
        $totalComponentes = $this->total_componentes ?: (int) ($this->componente?->cantidad_total ?? 0);

        return $this->piezasRevisadasParaTotal($totalComponentes ?: null);
    }

    public function piezasRevisadasParaTotal(?int $totalComponentes = null): array
    {
        $totalComponentes = (int) ($totalComponentes ?: $this->total_componentes ?: (int) ($this->componente?->cantidad_total ?? 0));
        $totalComponentes = max(0, $totalComponentes);

        $componentes = self::normalizarComponentesRevisados(
            $this->componentes_revisados,
            $totalComponentes > 0 ? $totalComponentes : null
        );

        if (!empty($componentes)) {
            return $componentes;
        }

        $cantidadRevisada = (int) ($this->cantidad_componentes_revisados ?? 0);

        if ($cantidadRevisada > 0 && $totalComponentes > 0) {
            return range(1, min($cantidadRevisada, $totalComponentes));
        }

        if ($totalComponentes === 1 && $this->exists) {
            return [1];
        }

        return [];
    }

    public function scopeUltimosPorComponente(Builder $query): Builder
    {
        $table = $this->getTable();

        $latestIds = DB::table($table . ' as actual')
            ->leftJoin($table . ' as mas_reciente', function ($join): void {
                $join->on('actual.linea_id', '=', 'mas_reciente.linea_id')
                    ->on('actual.componente_id', '=', 'mas_reciente.componente_id')
                    ->whereRaw("COALESCE(actual.maquina, '') = COALESCE(mas_reciente.maquina, '')")
                    ->where(function ($subQuery): void {
                        $subQuery->whereColumn('mas_reciente.fecha_analisis', '>', 'actual.fecha_analisis')
                            ->orWhere(function ($tieBreaker): void {
                                $tieBreaker->whereColumn('mas_reciente.fecha_analisis', '=', 'actual.fecha_analisis')
                                    ->whereColumn('mas_reciente.id', '>', 'actual.id');
                            });
                    });
            })
            ->whereNull('mas_reciente.id')
            ->select('actual.id');

        return $query->whereIn($this->qualifyColumn('id'), $latestIds);
    }
}
