<?php

namespace App\Services;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\HistorialRestablecimiento;
use App\Models\Linea;
use App\Support\LavadoraCatalog;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class LavadoraRevisionPeriodicityService
{
    public const PERIODICIDAD_MESES = [
        'CATARINAS' => 4,
        'GUI_INF_TANQUE' => 4,
        'GUI_INT_TANQUE' => 4,
        'GUI_SUP_TANQUE' => 4,
        'SERVO_CHICO' => 12,
        'SERVO_GRANDE' => 12,
        'BUJE_ESPIGA' => 12,
        'RV200' => 12,
        'RV200_SIN_FIN' => 12,
    ];

    public const COMPONENTES_CON_LADO = [
        'CATARINAS',
        'GUI_INF_TANQUE',
        'GUI_INT_TANQUE',
        'GUI_SUP_TANQUE',
    ];

    public const LADOS_COMPONENTE = [
        'VAPOR',
        'PASILLO',
    ];

    public const CANTIDADES_POR_LADO = [
        'L-04' => 13,
        'L-05' => 13,
        'L-06' => 15,
        'L-07' => 15,
        'L-08' => 13,
        'L-09' => 13,
        'L-12' => 13,
        'L-13' => 13,
    ];

    public const CANTIDADES_POR_LINEA = [
        'L-04' => 13,
        'L-05' => 14,
        'L-06' => 15,
        'L-07' => 15,
        'L-08' => 13,
        'L-09' => 13,
        'L-12' => 14,
        'L-13' => 14,
    ];

    public function estadisticasLinea(Linea $linea, ?CarbonInterface $fechaReferencia = null): array
    {
        $componentesLinea = LavadoraCatalog::componentesDeLinea($linea->nombre);
        $estadisticas = [];

        foreach ($componentesLinea as $codigo => $nombre) {
            $estadisticas[$codigo] = $this->resumenComponenteLinea(
                $linea,
                $codigo,
                $nombre,
                $this->cantidadTotalComponenteLinea($linea->nombre, $codigo),
                $fechaReferencia
            );
        }

        return $estadisticas;
    }

    public function resumenLinea(Linea $linea, ?CarbonInterface $fechaReferencia = null): array
    {
        $estadisticas = $this->estadisticasLinea($linea, $fechaReferencia);
        $totalGeneral = collect($estadisticas)->sum('cantidad_total');
        $revisadoGeneral = collect($estadisticas)->sum('cantidad_revisada');
        $ultimaRevision = collect($estadisticas)
            ->pluck('ultima_revision')
            ->filter()
            ->sortDesc()
            ->first();

        return [
            'total_general' => $totalGeneral,
            'revisado_general' => $revisadoGeneral,
            'porcentaje_general' => $totalGeneral > 0
                ? round(($revisadoGeneral / $totalGeneral) * 100, 1)
                : 0,
            'ultima_revision' => $ultimaRevision
                ? Carbon::parse($ultimaRevision)->format('d/m/Y')
                : null,
        ];
    }

    public function estadosComponentes(?Linea $linea = null, ?CarbonInterface $fechaReferencia = null): Collection
    {
        $lineas = $linea
            ? collect([$linea])
            : Linea::query()
                ->whereIn('nombre', LavadoraCatalog::LINEAS)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();

        return $lineas
            ->flatMap(function (Linea $linea) use ($fechaReferencia) {
                return collect($this->estadisticasLinea($linea, $fechaReferencia))
                    ->map(function (array $item): array {
                        return [
                            'linea_id' => $item['linea_id'],
                            'linea' => $item['linea'],
                            'codigo' => $item['codigo'],
                            'nombre' => $item['nombre'],
                            'periodo_meses' => $item['periodo_meses'],
                            'ultima_revision' => $item['ultima_revision_formateada'],
                            'proximo_vencimiento' => $item['proximo_vencimiento_formateado'],
                            'dias_restantes' => $item['dias_restantes'],
                            'estado' => $item['estado_periodicidad'],
                            'estado_label' => $item['estado_periodicidad_label'],
                            'color' => $item['color_periodicidad'],
                            'cantidad_revisada' => $item['cantidad_revisada'],
                            'cantidad_total' => $item['cantidad_total'],
                            'pendientes_reset' => $item['pendientes_reset'],
                            'ultimo_reset' => $item['ultimo_reset_formateado'],
                            'desglose_lados' => $item['desglose_lados'],
                        ];
                    });
            })
            ->values();
    }

    public function resetPendientes(?CarbonInterface $fechaReferencia = null, bool $simular = false): array
    {
        $fechaReferencia = $this->normalizarFechaReferencia($fechaReferencia);

        $analisis = AnalisisLavadora::query()
            ->with(['linea', 'componente', 'historialRestablecimientos'])
            ->get();

        $grupos = $this->agruparAnalisisPorIdentidad($analisis);

        $stats = [
            'fecha_referencia' => $fechaReferencia->toDateTimeString(),
            'total_analisis' => $analisis->count(),
            'componentes_evaluados' => $grupos->count(),
            'componentes_a_restablecer' => 0,
            'componentes_vigentes' => 0,
            'componentes_sin_periodicidad' => 0,
            'analisis_a_restablecer' => 0,
            'analisis_ya_restablecidos' => 0,
            'componentes_afectados' => [],
            'lineas_afectadas' => [],
            'detalles' => [],
        ];

        foreach ($grupos as $grupo) {
            $ordenados = $this->ordenarAnalisisPorFechaDesc($grupo);
            /** @var AnalisisLavadora|null $ultimoAnalisis */
            $ultimoAnalisis = $ordenados->first();

            if (!$ultimoAnalisis) {
                continue;
            }

            $codigoBase = $this->codigoBaseAnalisis($ultimoAnalisis);

            if (!$codigoBase || !isset(self::PERIODICIDAD_MESES[$codigoBase])) {
                $stats['componentes_sin_periodicidad']++;
                continue;
            }

            $fechaUltimaRevision = $this->fechaRevision($ultimoAnalisis);

            if (!$fechaUltimaRevision) {
                $stats['componentes_sin_periodicidad']++;
                continue;
            }

            $periodoMeses = self::PERIODICIDAD_MESES[$codigoBase];
            $proximoVencimiento = $fechaUltimaRevision->copy()->addMonths($periodoMeses)->endOfDay();
            $analisisSinReset = $grupo
                ->filter(fn (AnalisisLavadora $item): bool => !$this->analisisRestablecido($item))
                ->values();

            $stats['analisis_ya_restablecidos'] += $grupo->count() - $analisisSinReset->count();

            if ($analisisSinReset->isEmpty()) {
                continue;
            }

            if ($proximoVencimiento->gt($fechaReferencia)) {
                $stats['componentes_vigentes']++;
                continue;
            }

            $stats['componentes_a_restablecer']++;
            $stats['analisis_a_restablecer'] += $analisisSinReset->count();

            $lineaNombre = $ultimoAnalisis->linea?->nombre ?? 'N/A';
            if (!in_array($codigoBase, $stats['componentes_afectados'], true)) {
                $stats['componentes_afectados'][] = $codigoBase;
            }

            if (!in_array($lineaNombre, $stats['lineas_afectadas'], true)) {
                $stats['lineas_afectadas'][] = $lineaNombre;
            }

            $stats['detalles'][] = [
                'linea' => $lineaNombre,
                'componente' => $codigoBase,
                'reductor' => $ultimoAnalisis->reductor,
                'lado' => $ultimoAnalisis->lado,
                'ultima_revision' => $fechaUltimaRevision->toDateString(),
                'proximo_vencimiento' => $proximoVencimiento->toDateString(),
                'analisis' => $analisisSinReset->count(),
                'periodo' => $periodoMeses . ' meses',
            ];

            if ($simular) {
                continue;
            }

            foreach ($analisisSinReset as $item) {
                $this->registrarRestablecimiento($item, $fechaReferencia, $periodoMeses);
            }
        }

        sort($stats['componentes_afectados']);
        sort($stats['lineas_afectadas']);

        return $stats;
    }

    public function cantidadTotalLinea(string $lineaNombre): int
    {
        return self::CANTIDADES_POR_LINEA[$lineaNombre]
            ?? count(LavadoraCatalog::reductoresPorLinea($lineaNombre));
    }

    public function cantidadPorLadoLinea(string $lineaNombre): int
    {
        return self::CANTIDADES_POR_LADO[$lineaNombre]
            ?? count($this->reductoresEsperadosPorLado($lineaNombre));
    }

    public function cantidadTotalComponenteLinea(string $lineaNombre, string $codigoBase): int
    {
        if ($this->componenteRequiereLado($codigoBase)) {
            return $this->cantidadPorLadoLinea($lineaNombre) * count(self::LADOS_COMPONENTE);
        }

        return $this->cantidadTotalLinea($lineaNombre);
    }

    public function fechaRevision(AnalisisLavadora $analisis): ?Carbon
    {
        if ($analisis->fecha_analisis) {
            return Carbon::parse($analisis->fecha_analisis)->startOfDay();
        }

        return $analisis->created_at
            ? Carbon::parse($analisis->created_at)->startOfDay()
            : null;
    }

    public function codigoBaseAnalisis(AnalisisLavadora $analisis): ?string
    {
        $codigoBase = AnalisisLavadora::codigoBaseComponente($analisis->componente?->codigo);

        return isset(self::PERIODICIDAD_MESES[$codigoBase]) ? $codigoBase : null;
    }

    public function identidadComponente(AnalisisLavadora $analisis): ?string
    {
        $codigoBase = $this->codigoBaseAnalisis($analisis);

        if (!$codigoBase) {
            return null;
        }

        $reductor = LavadoraCatalog::normalizarReductor($analisis->reductor) ?? trim((string) $analisis->reductor);
        $lado = $this->normalizarLado($analisis->lado);

        return $this->identidadKey((int) $analisis->linea_id, $codigoBase, $reductor, $lado);
    }

    private function resumenComponenteLinea(
        Linea $linea,
        string $codigoBase,
        string $nombre,
        int $cantidadTotal,
        ?CarbonInterface $fechaReferencia = null
    ): array {
        $fechaReferencia = $this->normalizarFechaReferencia($fechaReferencia);
        $periodoMeses = self::PERIODICIDAD_MESES[$codigoBase] ?? 12;
        $componentIds = $this->resolverComponenteIdsPorCodigoBase($codigoBase, $linea->nombre);
        $identidades = $this->identidadesComponenteLinea($linea, $codigoBase, $fechaReferencia, $componentIds);

        $revisados = $identidades
            ->filter(fn (array $item): bool => $item['vigente'])
            ->count();

        $revisados = min($revisados, $cantidadTotal);
        $porcentaje = $cantidadTotal > 0 ? round(($revisados / $cantidadTotal) * 100, 1) : 0;

        $ultimaRevision = $this->fechaMaxima($identidades->pluck('fecha_revision_at'));
        $proximoVencimiento = $this->fechaMinima($identidades->pluck('proximo_vencimiento_at'));
        $ultimoReset = $this->ultimoResetComponente($linea, $componentIds);
        $pendientesReset = $identidades->where('estado', 'pendiente')->sum('pendientes_reset');
        $estado = $this->estadoResumen($identidades, $pendientesReset);

        return [
            'linea_id' => $linea->id,
            'linea' => $linea->nombre,
            'nombre' => $nombre,
            'codigo' => $codigoBase,
            'cantidad_total' => $cantidadTotal,
            'cantidad_revisada' => $revisados,
            'porcentaje' => $porcentaje,
            'color' => $this->colorPorcentaje($porcentaje),
            'reductores_detectados' => $revisados,
            'periodo_meses' => $periodoMeses,
            'fecha_inicio_periodo' => $ultimaRevision
                ? $ultimaRevision->copy()->subMonths($periodoMeses)->toDateString()
                : null,
            'fecha_fin_periodo' => $fechaReferencia->toDateString(),
            'ultima_revision' => $ultimaRevision?->toDateString(),
            'ultima_revision_formateada' => $ultimaRevision?->format('d/m/Y'),
            'proximo_vencimiento' => $proximoVencimiento?->toDateString(),
            'proximo_vencimiento_formateado' => $proximoVencimiento?->format('d/m/Y'),
            'dias_restantes' => $proximoVencimiento
                ? (int) $fechaReferencia->copy()->startOfDay()->diffInDays($proximoVencimiento->copy()->startOfDay(), false)
                : null,
            'estado_periodicidad' => $estado,
            'estado_periodicidad_label' => $this->estadoLabel($estado),
            'color_periodicidad' => $this->colorEstado($estado, $proximoVencimiento, $fechaReferencia),
            'pendientes_reset' => (int) $pendientesReset,
            'ultimo_reset' => $ultimoReset?->fecha_restablecimiento?->toDateString(),
            'ultimo_reset_formateado' => $ultimoReset?->fecha_restablecimiento?->format('d/m/Y H:i:s'),
            'requiere_lado' => $this->componenteRequiereLado($codigoBase),
            'desglose_lados' => $this->desgloseLados($identidades, $codigoBase, $linea->nombre),
        ];
    }

    private function identidadesComponenteLinea(
        Linea $linea,
        string $codigoBase,
        Carbon $fechaReferencia,
        array $componentIds
    ): Collection {
        $identidadesEsperadas = $this->identidadesEsperadasComponente($linea, $codigoBase);

        $analisis = empty($componentIds)
            ? collect()
            : AnalisisLavadora::query()
                ->with(['linea', 'componente', 'historialRestablecimientos'])
                ->where('linea_id', $linea->id)
                ->whereIn('componente_id', $componentIds)
                ->get();

        $analisisPorIdentidad = $this->agruparAnalisisPorIdentidad($analisis);

        return $identidadesEsperadas
            ->map(function (array $identidadEsperada) use ($analisisPorIdentidad, $codigoBase, $fechaReferencia): array {
                /** @var Collection<int, AnalisisLavadora> $grupo */
                $grupo = $analisisPorIdentidad->get($identidadEsperada['key'], collect());

                if ($grupo->isEmpty()) {
                    return [
                        'analisis_id' => null,
                        'componente_id' => null,
                        'reductor' => $identidadEsperada['reductor'],
                        'lado' => $identidadEsperada['lado'],
                        'fecha_revision_at' => null,
                        'proximo_vencimiento_at' => null,
                        'estado' => 'sin_revision',
                        'vigente' => false,
                        'pendientes_reset' => 0,
                    ];
                }

                $ordenados = $this->ordenarAnalisisPorFechaDesc($grupo);
                /** @var AnalisisLavadora $ultimoAnalisis */
                $ultimoAnalisis = $ordenados->first();
                $periodoMeses = self::PERIODICIDAD_MESES[$codigoBase] ?? 12;
                $fechaRevision = $this->fechaRevision($ultimoAnalisis);
                $proximoVencimiento = $fechaRevision?->copy()->addMonths($periodoMeses);
                $restablecido = $this->analisisRestablecido($ultimoAnalisis);
                $pendientesReset = $grupo->filter(fn (AnalisisLavadora $item): bool => !$this->analisisRestablecido($item))->count();
                $vencido = $proximoVencimiento
                    ? $proximoVencimiento->copy()->endOfDay()->lte($fechaReferencia)
                    : false;

                $estado = match (true) {
                    !$fechaRevision => 'sin_revision',
                    $vencido && $pendientesReset > 0 => 'pendiente',
                    $restablecido => 'restablecido',
                    default => 'programado',
                };

                return [
                    'analisis_id' => $ultimoAnalisis->id,
                    'componente_id' => $ultimoAnalisis->componente_id,
                    'reductor' => $identidadEsperada['reductor'],
                    'lado' => $identidadEsperada['lado'],
                    'fecha_revision_at' => $fechaRevision,
                    'proximo_vencimiento_at' => $proximoVencimiento,
                    'estado' => $estado,
                    'vigente' => $estado === 'programado',
                    'pendientes_reset' => $estado === 'pendiente' ? $pendientesReset : 0,
                ];
            })
            ->values();
    }

    private function resolverComponenteIdsPorCodigoBase(string $codigoBase, ?string $lineaNombre = null): array
    {
        return Componente::query()
            ->select('id', 'codigo', 'linea', 'activo')
            ->when($lineaNombre, function ($query) use ($lineaNombre): void {
                $query->where(function ($subQuery) use ($lineaNombre): void {
                    $subQuery->where('linea', $lineaNombre)
                        ->orWhereNull('linea');
                });
            })
            ->get()
            ->filter(fn (Componente $componente): bool => AnalisisLavadora::codigoBaseComponente($componente->codigo) === $codigoBase)
            ->pluck('id')
            ->values()
            ->all();
    }

    private function identidadesEsperadasComponente(Linea $linea, string $codigoBase): Collection
    {
        $reductores = $this->componenteRequiereLado($codigoBase)
            ? $this->reductoresEsperadosPorLado($linea->nombre)
            : $this->reductoresConfiguradosLinea($linea->nombre);

        return collect($reductores)
            ->flatMap(function (string $reductor) use ($linea, $codigoBase): array {
                if (!$this->componenteRequiereLado($codigoBase)) {
                    return [[
                        'key' => $this->identidadKey((int) $linea->id, $codigoBase, $reductor, null),
                        'reductor' => $reductor,
                        'lado' => null,
                    ]];
                }

                return collect(self::LADOS_COMPONENTE)
                    ->map(fn (string $lado): array => [
                        'key' => $this->identidadKey((int) $linea->id, $codigoBase, $reductor, $lado),
                        'reductor' => $reductor,
                        'lado' => $lado,
                    ])
                    ->all();
            })
            ->values();
    }

    private function reductoresConfiguradosLinea(string $lineaNombre): array
    {
        return collect(LavadoraCatalog::reductoresPorLinea($lineaNombre))
            ->map(fn ($reductor): ?string => LavadoraCatalog::normalizarReductor($reductor))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function reductoresEsperadosPorLado(string $lineaNombre): array
    {
        $reductores = $this->reductoresConfiguradosLinea($lineaNombre);
        $cantidadPorLado = self::CANTIDADES_POR_LADO[$lineaNombre] ?? count($reductores);

        if (count($reductores) > $cantidadPorLado) {
            $sinFlechaLoca = collect($reductores)
                ->reject(fn (string $reductor): bool => $reductor === LavadoraCatalog::FLECHA_LOCA)
                ->values()
                ->all();

            if (count($sinFlechaLoca) >= $cantidadPorLado) {
                $reductores = $sinFlechaLoca;
            }
        }

        return array_slice($reductores, 0, $cantidadPorLado);
    }

    private function componenteRequiereLado(string $codigoBase): bool
    {
        return in_array($codigoBase, self::COMPONENTES_CON_LADO, true);
    }

    private function identidadKey(int $lineaId, string $codigoBase, ?string $reductor, ?string $lado): string
    {
        return implode('|', [
            (string) $lineaId,
            $codigoBase,
            LavadoraCatalog::normalizarReductor($reductor) ?? trim((string) $reductor),
            $this->normalizarLado($lado) ?? '',
        ]);
    }

    private function normalizarLado(?string $lado): ?string
    {
        $lado = strtoupper(trim((string) $lado));

        return in_array($lado, self::LADOS_COMPONENTE, true)
            ? $lado
            : null;
    }

    private function desgloseLados(Collection $identidades, string $codigoBase, string $lineaNombre): ?array
    {
        if (!$this->componenteRequiereLado($codigoBase)) {
            return null;
        }

        $totalPorLado = $this->cantidadPorLadoLinea($lineaNombre);

        return collect(self::LADOS_COMPONENTE)
            ->mapWithKeys(function (string $lado) use ($identidades, $totalPorLado): array {
                $revisados = $identidades
                    ->filter(fn (array $item): bool => $item['lado'] === $lado && $item['vigente'])
                    ->count();

                return [
                    $lado => [
                        'total' => $totalPorLado,
                        'revisados' => min($revisados, $totalPorLado),
                        'pendientes' => max(0, $totalPorLado - $revisados),
                    ],
                ];
            })
            ->all();
    }

    private function agruparAnalisisPorIdentidad(Collection $analisis): Collection
    {
        return $analisis
            ->filter(fn (AnalisisLavadora $item): bool => $this->identidadComponente($item) !== null)
            ->groupBy(fn (AnalisisLavadora $item): string => (string) $this->identidadComponente($item));
    }

    private function ordenarAnalisisPorFechaDesc(Collection $analisis): Collection
    {
        return $analisis
            ->sort(function (AnalisisLavadora $a, AnalisisLavadora $b): int {
                $fechaA = $this->fechaRevision($a)?->getTimestamp() ?? 0;
                $fechaB = $this->fechaRevision($b)?->getTimestamp() ?? 0;

                if ($fechaA === $fechaB) {
                    return ($b->id ?? 0) <=> ($a->id ?? 0);
                }

                return $fechaB <=> $fechaA;
            })
            ->values();
    }

    private function registrarRestablecimiento(AnalisisLavadora $analisis, Carbon $fechaReferencia, int $periodoMeses): void
    {
        HistorialRestablecimiento::query()->firstOrCreate(
            ['analisis_id' => $analisis->id],
            [
                'linea_id' => $analisis->linea_id,
                'componente_id' => $analisis->componente_id,
                'reductor' => $analisis->reductor,
                'lado' => $analisis->lado,
                'fecha_analisis_original' => $this->fechaRevision($analisis)?->toDateString() ?? $fechaReferencia->toDateString(),
                'fecha_restablecimiento' => $fechaReferencia,
                'motivo' => 'periodicidad_componente',
                'periodo_meses' => $periodoMeses,
            ]
        );
    }

    private function analisisRestablecido(AnalisisLavadora $analisis): bool
    {
        if ($analisis->relationLoaded('historialRestablecimientos')) {
            return $analisis->historialRestablecimientos->isNotEmpty();
        }

        return HistorialRestablecimiento::query()
            ->where('analisis_id', $analisis->id)
            ->exists();
    }

    private function ultimoResetComponente(Linea $linea, array $componentIds): ?HistorialRestablecimiento
    {
        if (empty($componentIds)) {
            return null;
        }

        return HistorialRestablecimiento::query()
            ->where('linea_id', $linea->id)
            ->whereIn('componente_id', $componentIds)
            ->orderByDesc('fecha_restablecimiento')
            ->first();
    }

    private function fechaMaxima(Collection $fechas): ?Carbon
    {
        return $fechas
            ->filter()
            ->sortByDesc(fn (Carbon $fecha): int => $fecha->getTimestamp())
            ->first();
    }

    private function fechaMinima(Collection $fechas): ?Carbon
    {
        return $fechas
            ->filter()
            ->sortBy(fn (Carbon $fecha): int => $fecha->getTimestamp())
            ->first();
    }

    private function normalizarFechaReferencia(?CarbonInterface $fechaReferencia = null): Carbon
    {
        return $fechaReferencia
            ? Carbon::parse($fechaReferencia)->endOfDay()
            : Carbon::now()->endOfDay();
    }

    private function estadoResumen(Collection $identidades, int $pendientesReset): string
    {
        if (
            $identidades->isEmpty()
            || $identidades->every(fn (array $item): bool => $item['estado'] === 'sin_revision')
        ) {
            return 'sin_revision';
        }

        if ($pendientesReset > 0) {
            return 'pendiente';
        }

        if ($identidades->contains(fn (array $item): bool => $item['vigente'])) {
            return 'programado';
        }

        return 'restablecido';
    }

    private function estadoLabel(string $estado): string
    {
        return match ($estado) {
            'pendiente' => 'Pendiente',
            'programado' => 'Programado',
            'restablecido' => 'Restablecido',
            default => 'Sin revision',
        };
    }

    private function colorEstado(string $estado, ?Carbon $proximoVencimiento, Carbon $fechaReferencia): string
    {
        if ($estado === 'pendiente') {
            return 'danger';
        }

        if ($estado === 'restablecido' || $estado === 'sin_revision' || !$proximoVencimiento) {
            return 'secondary';
        }

        $dias = (int) $fechaReferencia->copy()->startOfDay()->diffInDays($proximoVencimiento->copy()->startOfDay(), false);

        if ($dias <= 7) {
            return 'warning';
        }

        if ($dias <= 15) {
            return 'info';
        }

        return 'success';
    }

    private function colorPorcentaje(float|int $porcentaje): string
    {
        if ($porcentaje >= 80) {
            return 'success';
        }

        if ($porcentaje >= 50) {
            return 'info';
        }

        if ($porcentaje >= 20) {
            return 'warning';
        }

        return 'danger';
    }
}
