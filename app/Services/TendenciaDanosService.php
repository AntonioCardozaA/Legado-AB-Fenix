<?php

namespace App\Services;

use App\Models\AnalisisLavadora;
use App\Models\AnalisisPasteurizadora;
use App\Models\Linea;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;

class TendenciaDanosService
{
    public const TIPO_LAVADORAS = 'lavadoras';
    public const TIPO_PASTEURIZADORAS = 'pasteurizadoras';

    private const DAMAGE_STATES = [
        'danado - requiere cambio',
        'dano - requiere cambio',
        'danado - cambiado',
        'dano - cambiado',
        'danado',
        'dano',
        'desgaste severo',
        'desgaste moderado',
    ];

    public function ventanas52124(): array
    {
        return [
            ['key' => 'semanas_52', 'label' => '52 semanas', 'type' => 'weeks', 'size' => 52],
            ['key' => 'semanas_12', 'label' => '12 semanas', 'type' => 'weeks', 'size' => 12],
            ['key' => 'semanas_4', 'label' => '4 semanas', 'type' => 'weeks', 'size' => 4],
        ];
    }

    public function ventanas30147(): array
    {
        return [
            ['key' => 'dias_30', 'label' => '30 dias', 'type' => 'days', 'size' => 30],
            ['key' => 'dias_14', 'label' => '14 dias', 'type' => 'days', 'size' => 14],
            ['key' => 'dias_7', 'label' => '7 dias', 'type' => 'days', 'size' => 7],
        ];
    }

    public function criteriosDano(): array
    {
        return [
            'Dañado - Requiere cambio',
            'Dañado - Cambiado',
            'Desgaste severo',
            'Desgaste moderado',
        ];
    }

    public function calcularPorLineas($lineas, string $tipoEquipo, Carbon $referencia, array $ventanas): array
    {
        $lineas = collect($lineas)->values();

        if ($lineas->isEmpty()) {
            return [];
        }

        $referencia = $referencia->copy()->endOfDay();
        $rangos = collect($ventanas)
            ->map(fn (array $ventana) => $this->resolverRangosVentana($ventana, $referencia));
        $inicioFuente = $rangos
            ->map(fn (array $rango) => $rango['previous_start'])
            ->sortBy(fn (Carbon $fecha) => $fecha->getTimestamp())
            ->first() ?? $referencia->copy()->startOfDay();

        $eventos = $this->obtenerEventos($lineas, $tipoEquipo, [
            'from' => $inicioFuente,
            'to' => $referencia,
        ])->groupBy('linea_id');

        return $lineas
            ->mapWithKeys(function (Linea $linea) use ($eventos, $ventanas, $referencia) {
                return [
                    $linea->id => $this->resumirLinea(
                        $eventos->get($linea->id, collect()),
                        $ventanas,
                        $referencia
                    ),
                ];
            })
            ->all();
    }

    public function calcularParaLinea(Linea $linea, string $tipoEquipo, Carbon $referencia, array $ventanas): array
    {
        return $this->calcularPorLineas(collect([$linea]), $tipoEquipo, $referencia, $ventanas)[$linea->id]
            ?? $this->resumenVacio($ventanas);
    }

    public function construirDashboard($lineas, string $tipoEquipo, array $ventanas, ?array $rangoVisible = null): array
    {
        $lineas = collect($lineas)->values();

        if ($lineas->isEmpty()) {
            return [
                'default_linea_id' => null,
                'lineas' => [],
                'criterios' => $this->criteriosDano(),
                'global' => $this->resumenGlobalVacio(),
                'periodo' => $this->resolverPeriodoDashboard(collect(), $rangoVisible),
            ];
        }

        $rangoVisible = $this->normalizarRangoVisibleParaDetalle($rangoVisible, $ventanas);
        $rangoFuente = $this->ampliarRangoFuente($rangoVisible, $ventanas);
        $eventosFuente = $this->obtenerEventos($lineas, $tipoEquipo, $rangoFuente)->values();
        $eventosPeriodo = $this->filtrarEventosPorRangoVisible($eventosFuente, $rangoVisible)->values();
        $eventosPorLineaFuente = $eventosFuente->groupBy('linea_id');
        $eventosPorLineaPeriodo = $eventosPeriodo->groupBy('linea_id');
        $cortes = $this->construirCortesMensuales(
            $eventosPeriodo->isNotEmpty() ? $eventosPeriodo : $eventosFuente,
            $rangoVisible
        );
        $labels = $cortes->pluck('label')->all();
        $global = $this->resumirGlobal($eventosPeriodo, $lineas);
        $global['graficas'] = $this->construirGraficasGlobal($global);
        $periodo = $this->resolverPeriodoDashboard($eventosPeriodo, $rangoVisible);

        $seriesPorLinea = $lineas
            ->map(function (Linea $linea) use ($eventosPorLineaFuente, $eventosPorLineaPeriodo, $ventanas, $cortes, $labels, $global, $periodo) {
                $eventosLineaFuente = $eventosPorLineaFuente
                    ->get($linea->id, collect())
                    ->sortBy(fn (array $item) => $item['occurred_at']->getTimestamp())
                    ->values();
                $eventosLineaPeriodo = $eventosPorLineaPeriodo
                    ->get($linea->id, collect())
                    ->sortBy(fn (array $item) => $item['occurred_at']->getTimestamp())
                    ->values();

                $series = collect($ventanas)
                    ->map(function (array $ventana) use ($eventosLineaFuente, $cortes) {
                        return [
                            'key' => $ventana['key'],
                            'label' => $ventana['label'],
                            'data' => $cortes
                                ->map(fn (array $corte) => $this->contarEventosEnVentana($eventosLineaFuente, $ventana, $corte['cut_at']))
                                ->all(),
                        ];
                    })
                    ->values();

                $ultimoEvento = $eventosLineaPeriodo->last();
                $ultimoCorte = $labels ? end($labels) : null;
                $resumenSeries = $series
                    ->mapWithKeys(fn (array $serie) => [$serie['key'] => (int) collect($serie['data'])->last()])
                    ->all();
                $componentes = $this->resumirComponentesPeriodo($eventosLineaPeriodo);
                $danos = $this->resumirUltimosDanosPorComponente($eventosLineaPeriodo);
                $resumenPeriodo = $this->resumirPeriodoLinea($eventosLineaPeriodo, $componentes, $danos, $global['total_fallas'] ?? 0, $cortes);
                $seriesComponentes = $this->construirSeriesComponentesMensuales($eventosLineaPeriodo, $cortes, $componentes);
                $totalMensual = $this->construirSerieTotalMensual($eventosLineaPeriodo, $cortes);
                $graficas = $this->construirGraficasLinea($labels, $componentes, $danos, $seriesComponentes, $totalMensual, $series->all(), $ventanas, $cortes, $eventosLineaPeriodo);
                $ventanasResumen = $labels
                    ? $this->resumirLinea($eventosLineaFuente, $ventanas, $cortes->last()['cut_at'])['ventanas']
                    : $this->resumenVacio($ventanas)['ventanas'];

                return [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                    'labels' => $labels,
                    'series' => $series->all(),
                    'resumen' => [
                        'ultimo_corte' => $ultimoCorte,
                        'ultima_falla' => $ultimoEvento['fecha_humana'] ?? null,
                        'ultima_fuente' => $ultimoEvento['type_label'] ?? null,
                        'total_fallas' => $eventosLineaPeriodo->count(),
                        'periodo' => $periodo,
                    ] + $resumenPeriodo + $resumenSeries,
                    'ventanas' => $ventanasResumen,
                    'componentes' => $componentes,
                    'danos' => $danos,
                    'matriz_componentes_danos' => $this->construirMatrizComponentesDanos($componentes),
                    'componentes_series' => $seriesComponentes,
                    'total_mensual' => $totalMensual,
                    'graficas' => $graficas,
                    'eventos' => collect($this->formatearEventosVentana($eventosLineaPeriodo))->take(12)->all(),
                    'sin_datos' => $eventosLineaPeriodo->isEmpty(),
                ] + $series->mapWithKeys(fn (array $serie) => [$serie['key'] => $serie['data']])->all();
            })
            ->values();

        $default = $seriesPorLinea->firstWhere('sin_datos', false);

        return [
            'default_linea_id' => $default['linea_id'] ?? $lineas->first()?->id,
            'lineas' => $seriesPorLinea->all(),
            'criterios' => $this->criteriosDano(),
            'global' => $global,
            'periodo' => $periodo,
        ];
    }

    public function construirFilasMensuales(
        Linea $linea,
        string $tipoEquipo,
        int $meses = 12,
        ?Carbon $fin = null,
        ?Carbon $inicioFiltro = null
    ): Collection
    {
        $inicioFiltro = $inicioFiltro?->copy()->startOfDay();
        $fin = $fin
            ? $fin->copy()->endOfDay()
            : now()->copy()->endOfMonth()->endOfDay();
        $inicio = $inicioFiltro
            ? $inicioFiltro->copy()->startOfMonth()
            : $fin->copy()->subMonthsNoOverflow(max($meses - 1, 0))->startOfMonth();
        $inicioFuente = collect($this->ventanas52124())
            ->merge($this->ventanas30147())
            ->map(fn (array $ventana) => $this->resolverRangoActual($ventana, $inicio->copy()->endOfMonth())['current_start'])
            ->sortBy(fn (Carbon $fecha) => $fecha->getTimestamp())
            ->first();

        $eventos = $this->obtenerEventos(collect([$linea]), $tipoEquipo, [
            'from' => $inicioFiltro ?: $inicioFuente,
            'to' => $fin,
        ])->where('linea_id', $linea->id)->values();

        $filas = collect();
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            $corte = $cursor->isSameMonth($fin)
                ? $fin->copy()->endOfDay()
                : $cursor->copy()->endOfMonth()->endOfDay();
            $rango52 = $this->resolverRangoActual($this->ventanas52124()[0], $corte);
            $rango12 = $this->resolverRangoActual($this->ventanas52124()[1], $corte);
            $rango4 = $this->resolverRangoActual($this->ventanas52124()[2], $corte);
            $rango30 = $this->resolverRangoActual($this->ventanas30147()[0], $corte);
            $rango14 = $this->resolverRangoActual($this->ventanas30147()[1], $corte);
            $rango7 = $this->resolverRangoActual($this->ventanas30147()[2], $corte);

            $fila = new stdClass();
            $fila->id = sprintf('%d-%04d%02d', $linea->id, (int) $cursor->year, (int) $cursor->month);
            $fila->linea_id = $linea->id;
            $fila->linea = $linea;
            $fila->anio = (int) $cursor->year;
            $fila->mes = (int) $cursor->month;
            $fila->mesNombre = $this->nombreMes((int) $cursor->month);
            $fila->periodo = $fila->mesNombre . ' ' . $fila->anio;
            $fila->total_danos_52_semanas = $this->contarEventosEnRango($eventos, $rango52['current_start'], $rango52['current_end']);
            $fila->total_danos_12_semanas = $this->contarEventosEnRango($eventos, $rango12['current_start'], $rango12['current_end']);
            $fila->total_danos_4_semanas = $this->contarEventosEnRango($eventos, $rango4['current_start'], $rango4['current_end']);
            $fila->total_danos_30_dias = $this->contarEventosEnRango($eventos, $rango30['current_start'], $rango30['current_end']);
            $fila->total_danos_14_dias = $this->contarEventosEnRango($eventos, $rango14['current_start'], $rango14['current_end']);
            $fila->total_danos_7_dias = $this->contarEventosEnRango($eventos, $rango7['current_start'], $rango7['current_end']);
            $fila->fecha_corte_52 = $rango52['current_start'];
            $fila->fecha_corte_12 = $rango12['current_start'];
            $fila->fecha_corte_4 = $rango4['current_start'];
            $fila->fecha_corte_30 = $rango30['current_start'];
            $fila->fecha_corte_14 = $rango14['current_start'];
            $fila->fecha_corte_7 = $rango7['current_start'];
            $fila->observaciones = 'Calculado automaticamente desde los analisis registrados.';
            $filas->push($fila);

            $cursor->addMonthNoOverflow();
        }

        return $filas
            ->values()
            ->tap(function (Collection $rows) {
                $previa = null;

                $rows->each(function ($fila) use (&$previa) {
                    $fila->variacion_52_semanas = $this->calcularVariacion($previa?->total_danos_52_semanas, $fila->total_danos_52_semanas);
                    $fila->variacion_12_semanas = $this->calcularVariacion($previa?->total_danos_12_semanas, $fila->total_danos_12_semanas);
                    $fila->variacion_4_semanas = $this->calcularVariacion($previa?->total_danos_4_semanas, $fila->total_danos_4_semanas);
                    $fila->variacion_30_dias = $this->calcularVariacion($previa?->total_danos_30_dias, $fila->total_danos_30_dias);
                    $fila->variacion_14_dias = $this->calcularVariacion($previa?->total_danos_14_dias, $fila->total_danos_14_dias);
                    $fila->variacion_7_dias = $this->calcularVariacion($previa?->total_danos_7_dias, $fila->total_danos_7_dias);
                    $previa = $fila;
                });
            })
            ->sortByDesc(fn ($fila) => sprintf('%04d%02d', $fila->anio, $fila->mes))
            ->values();
    }

    public function obtenerEventos($lineas, string $tipoEquipo, ?array $rango = null): Collection
    {
        $lineas = collect($lineas)->values();

        if ($lineas->isEmpty()) {
            return collect();
        }

        $tipoEquipo = $this->normalizarTipo($tipoEquipo);
        $lineaIds = $lineas->pluck('id');
        $lineaNombres = $lineas->pluck('nombre', 'id');
        $desde = ($rango['from'] ?? null) instanceof Carbon ? $rango['from']->copy()->startOfDay() : null;
        $hasta = ($rango['to'] ?? null) instanceof Carbon ? $rango['to']->copy()->endOfDay() : null;

        if ($tipoEquipo === self::TIPO_PASTEURIZADORAS) {
            return AnalisisPasteurizadora::queryForArea(AnalisisPasteurizadora::AREA_MECANICA)
                ->with('linea:id,nombre')
                ->whereIn('linea_id', $lineaIds)
                ->when($desde, fn ($query) => $query->where('fecha_analisis', '>=', $desde))
                ->when($hasta, fn ($query) => $query->where('fecha_analisis', '<=', $hasta))
                ->orderBy('fecha_analisis')
                ->orderBy('id')
                ->get()
                ->filter(fn (AnalisisPasteurizadora $item) => $this->esEstadoDano($item->estado))
                ->map(function (AnalisisPasteurizadora $item) use ($lineaNombres) {
                    $fecha = Carbon::parse($item->fecha_analisis ?? $item->created_at)->startOfDay();
                    $lado = $this->normalizarLado($item->lado);
                    $ubicacion = collect([
                        $item->modulo ? 'Modulo ' . $item->modulo : null,
                        $item->nivel ? 'Nivel ' . $item->nivel : null,
                        $lado,
                    ])->filter()->implode(' / ') ?: null;

                    return [
                        'id' => $item->id,
                        'source' => 'componente',
                        'type_label' => $item->estado,
                        'type_key' => $this->normalizarEstadoDano($item->estado),
                        'linea_id' => $item->linea_id,
                        'linea' => $item->linea?->nombre ?? $lineaNombres->get($item->linea_id),
                        'componente' => $item->componente,
                        'componente_codigo' => null,
                        'modulo' => $item->modulo,
                        'nivel' => $item->nivel,
                        'lado' => $lado,
                        'ubicacion' => $ubicacion,
                        'occurred_at' => $fecha,
                        'fecha_humana' => $fecha->format('d/m/Y'),
                    ];
                })
                ->values();
        }

        return AnalisisLavadora::query()
            ->with(['linea:id,nombre', 'componente:id,nombre,codigo'])
            ->whereIn('linea_id', $lineaIds)
            ->when($desde, fn ($query) => $query->where('fecha_analisis', '>=', $desde))
            ->when($hasta, fn ($query) => $query->where('fecha_analisis', '<=', $hasta))
            ->orderBy('fecha_analisis')
            ->orderBy('id')
            ->get()
            ->filter(fn (AnalisisLavadora $item) => $this->esEstadoDano($item->estado))
            ->map(function (AnalisisLavadora $item) use ($lineaNombres) {
                $fecha = Carbon::parse($item->fecha_analisis ?? $item->created_at)->startOfDay();
                $lado = $this->normalizarLado($item->lado);
                $ubicacion = collect([$item->reductor, $lado])->filter()->implode(' / ') ?: null;

                return [
                    'id' => $item->id,
                    'source' => 'componente',
                    'type_label' => $item->estado,
                    'type_key' => $this->normalizarEstadoDano($item->estado),
                    'linea_id' => $item->linea_id,
                    'linea' => $item->linea?->nombre ?? $lineaNombres->get($item->linea_id),
                    'componente' => $item->componente?->nombre ?? $item->componente?->codigo,
                    'componente_codigo' => $item->componente?->codigo,
                    'reductor' => $item->reductor,
                    'lado' => $lado,
                    'ubicacion' => $ubicacion,
                    'occurred_at' => $fecha,
                    'fecha_humana' => $fecha->format('d/m/Y'),
                ];
            })
            ->values();
    }

    public function resumenVacio(array $ventanas): array
    {
        $ventanasResumen = collect($ventanas)
            ->map(fn (array $ventana) => [
                'key' => $ventana['key'],
                'label' => $ventana['label'],
                'current' => 0,
                'previous' => 0,
                'delta' => 0,
                'trend' => 'stable',
                'tone' => 'info',
                'current_range' => null,
                'previous_range' => null,
                'current_componentes' => 0,
                'previous_componentes' => 0,
                'current_lados' => [],
                'previous_lados' => [],
                'current_eventos' => [],
                'previous_eventos' => [],
            ])
            ->values();

        return [
            'ventanas' => $ventanasResumen->all(),
            'resumen' => [
                'principal_actual' => 0,
                'principal_label' => $ventanasResumen->first()['label'] ?? null,
                'corto_actual' => 0,
                'corto_label' => $ventanasResumen->last()['label'] ?? null,
                'total_actual' => 0,
                'total_anterior' => 0,
                'ultima_falla' => null,
                'ultima_fuente' => null,
                'estado' => ['label' => 'Sin fallas', 'tone' => 'success'],
            ],
        ];
    }

    private function resumirLinea($eventos, array $ventanas, Carbon $referencia): array
    {
        $eventos = collect($eventos)->sortBy(fn (array $item) => $item['occurred_at']->getTimestamp())->values();
        $ventanasResumen = collect($ventanas)
            ->map(function (array $ventana) use ($eventos, $referencia) {
                $rangos = $this->resolverRangosVentana($ventana, $referencia);
                $actuales = $eventos
                    ->filter(fn (array $item) => $item['occurred_at']->between($rangos['current_start'], $rangos['current_end'], true))
                    ->values();
                $anteriores = $eventos
                    ->filter(fn (array $item) => $item['occurred_at']->between($rangos['previous_start'], $rangos['previous_end'], true))
                    ->values();
                $actual = $actuales->count();
                $anterior = $anteriores->count();
                $delta = $actual - $anterior;
                $trend = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'stable');

                return [
                    'key' => $ventana['key'],
                    'label' => $ventana['label'],
                    'current' => $actual,
                    'previous' => $anterior,
                    'delta' => $delta,
                    'trend' => $trend,
                    'tone' => $this->resolverTono($trend, $actual),
                    'current_range' => $this->formatearRango($rangos['current_start'], $rangos['current_end']),
                    'previous_range' => $this->formatearRango($rangos['previous_start'], $rangos['previous_end']),
                    'current_componentes' => $actuales->where('source', 'componente')->count(),
                    'previous_componentes' => $anteriores->where('source', 'componente')->count(),
                    'current_lados' => $this->contarEventosPorLado($actuales),
                    'previous_lados' => $this->contarEventosPorLado($anteriores),
                    'current_eventos' => $this->formatearEventosVentana($actuales),
                    'previous_eventos' => $this->formatearEventosVentana($anteriores),
                ];
            })
            ->values();

        $ultimaFalla = $eventos->last();

        return [
            'ventanas' => $ventanasResumen->all(),
            'resumen' => [
                'principal_actual' => (int) ($ventanasResumen->first()['current'] ?? 0),
                'principal_label' => $ventanasResumen->first()['label'] ?? null,
                'corto_actual' => (int) ($ventanasResumen->last()['current'] ?? 0),
                'corto_label' => $ventanasResumen->last()['label'] ?? null,
                'total_actual' => $ventanasResumen->sum('current'),
                'total_anterior' => $ventanasResumen->sum('previous'),
                'ultima_falla' => $ultimaFalla['fecha_humana'] ?? null,
                'ultima_fuente' => $ultimaFalla['type_label'] ?? null,
                'estado' => $this->resolverEstado($ventanasResumen),
            ],
        ];
    }

    private function ampliarRangoFuente(array $rangoVisible, array $ventanas): array
    {
        $desde = ($rangoVisible['from'] ?? null) instanceof Carbon ? $rangoVisible['from']->copy() : null;
        $hasta = ($rangoVisible['to'] ?? null) instanceof Carbon ? $rangoVisible['to']->copy() : null;

        if (!$desde && !$hasta) {
            return $rangoVisible;
        }

        if (!$desde) {
            return [
                'from' => null,
                'to' => $hasta?->copy()->endOfDay(),
            ];
        }

        $referencia = ($desde ?: $hasta)->copy()->endOfDay();
        $inicioFuente = collect($ventanas)
            ->map(fn (array $ventana) => $this->resolverRangoActual($ventana, $referencia)['current_start'])
            ->sortBy(fn (Carbon $fecha) => $fecha->getTimestamp())
            ->first();

        return [
            'from' => $inicioFuente ? $inicioFuente->copy()->startOfDay() : $desde,
            'to' => $hasta?->copy()->endOfDay(),
        ];
    }

    private function normalizarRangoVisibleParaDetalle(?array $rangoVisible, array $ventanas): array
    {
        $hasta = ($rangoVisible['to'] ?? null) instanceof Carbon
            ? $rangoVisible['to']->copy()->endOfDay()
            : now()->copy()->endOfDay();
        $desde = ($rangoVisible['from'] ?? null) instanceof Carbon
            ? $rangoVisible['from']->copy()->startOfDay()
            : null;

        if (!$desde) {
            $ventanaPrincipal = collect($ventanas)->first();

            if ($ventanaPrincipal) {
                $rangoPrincipal = $this->resolverRangoActual($ventanaPrincipal, $hasta);
                $desde = $rangoPrincipal['current_start']->copy()->startOfDay();
                $hasta = $rangoPrincipal['current_end']->copy()->endOfDay();
            }
        }

        return [
            'from' => $desde,
            'to' => $hasta,
        ];
    }

    private function construirCortesMensuales(Collection $eventos, ?array $rangoVisible = null): Collection
    {
        $eventosOrdenados = $eventos->sortBy(fn (array $item) => $item['occurred_at']->getTimestamp())->values();
        $primerEvento = $eventosOrdenados->first()['occurred_at'] ?? null;
        $ultimoEvento = $eventosOrdenados->last()['occurred_at'] ?? null;
        $inicio = ($rangoVisible['from'] ?? null) instanceof Carbon ? $rangoVisible['from']->copy() : ($primerEvento ? $primerEvento->copy() : null);
        $fin = ($rangoVisible['to'] ?? null) instanceof Carbon ? $rangoVisible['to']->copy() : ($ultimoEvento ? $ultimoEvento->copy() : null);

        if (!$inicio || !$fin || $inicio->gt($fin)) {
            return collect();
        }

        $cursor = $inicio->copy()->startOfMonth();
        $ultimoMes = $fin->copy()->startOfMonth();
        $cortes = collect();

        while ($cursor->lte($ultimoMes)) {
            $corte = $cursor->copy()->endOfMonth()->endOfDay();

            if ($cursor->isSameMonth($fin)) {
                $corte = $fin->copy()->endOfDay();
            }

            $cortes->push([
                'label' => $this->nombreMesCorto((int) $cursor->month) . ' ' . $cursor->year,
                'cut_at' => $corte,
            ]);

            $cursor->addMonthNoOverflow();
        }

        return $cortes;
    }

    private function resumenGlobalVacio(): array
    {
        return [
            'total_fallas' => 0,
            'lavadoras_con_fallas' => 0,
            'componentes_afectados' => 0,
            'tipos_dano' => 0,
            'lavadora_mas_fallas' => null,
            'componente_mayor_incidencia' => null,
            'dano_mas_frecuente' => null,
            'top_lavadoras' => [],
            'top_componentes' => [],
            'top_danos' => [],
        ];
    }

    private function resolverPeriodoDashboard(Collection $eventos, ?array $rangoVisible = null): array
    {
        $eventosOrdenados = $eventos->sortBy(fn (array $item) => $item['occurred_at']->getTimestamp())->values();
        $desde = ($rangoVisible['from'] ?? null) instanceof Carbon
            ? $rangoVisible['from']->copy()->startOfDay()
            : ($eventosOrdenados->first()['occurred_at'] ?? null);
        $hasta = ($rangoVisible['to'] ?? null) instanceof Carbon
            ? $rangoVisible['to']->copy()->endOfDay()
            : ($eventosOrdenados->last()['occurred_at'] ?? null);

        return [
            'desde' => $desde?->format('d/m/Y'),
            'hasta' => $hasta?->format('d/m/Y'),
            'desde_input' => $desde?->toDateString(),
            'hasta_input' => $hasta?->toDateString(),
            'label' => $desde && $hasta
                ? $desde->format('d/m/Y') . ' - ' . $hasta->format('d/m/Y')
                : 'Historico disponible',
            'historico_desde_inicio' => !(($rangoVisible['from'] ?? null) instanceof Carbon),
        ];
    }

    private function filtrarEventosPorRangoVisible(Collection $eventos, ?array $rangoVisible = null): Collection
    {
        $desde = ($rangoVisible['from'] ?? null) instanceof Carbon ? $rangoVisible['from']->copy()->startOfDay() : null;
        $hasta = ($rangoVisible['to'] ?? null) instanceof Carbon ? $rangoVisible['to']->copy()->endOfDay() : null;

        if (!$desde && !$hasta) {
            return $eventos->values();
        }

        return $eventos
            ->filter(function (array $item) use ($desde, $hasta) {
                $fecha = $item['occurred_at'] ?? null;

                if (!$fecha instanceof Carbon) {
                    return false;
                }

                return (!$desde || $fecha->gte($desde)) && (!$hasta || $fecha->lte($hasta));
            })
            ->values();
    }

    private function resumirGlobal(Collection $eventos, Collection $lineas): array
    {
        if ($eventos->isEmpty()) {
            return $this->resumenGlobalVacio();
        }

        $total = $eventos->count();
        $componentes = $this->resumirComponentesPeriodo($eventos);
        $danos = $this->resumirUltimosDanosPorComponente($eventos);
        $lineaNombres = $lineas->pluck('nombre', 'id');
        $topLavadoras = $eventos
            ->groupBy('linea_id')
            ->map(function (Collection $grupo, $lineaId) use ($total, $lineaNombres) {
                $componentesLinea = $this->resumirComponentesPeriodo($grupo);
                $danosLinea = $this->resumirUltimosDanosPorComponente($grupo);
                $ultimo = $grupo->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())->first();

                return [
                    'linea_id' => (int) $lineaId,
                    'linea' => $ultimo['linea'] ?? $lineaNombres->get($lineaId),
                    'total' => $grupo->count(),
                    'porcentaje' => $this->porcentaje($grupo->count(), $total),
                    'componentes_afectados' => count($componentesLinea),
                    'componente_critico' => $componentesLinea[0]['componente'] ?? null,
                    'dano_mas_frecuente' => $danosLinea[0]['estado'] ?? null,
                    'ultima_falla' => $ultimo['fecha_humana'] ?? null,
                ];
            })
            ->sort(function (array $a, array $b) {
                return ($b['total'] <=> $a['total']) ?: strcmp((string) $a['linea'], (string) $b['linea']);
            })
            ->values();

        return [
            'total_fallas' => $total,
            'lavadoras_con_fallas' => $topLavadoras->count(),
            'componentes_afectados' => count($componentes),
            'tipos_dano' => count($danos),
            'lavadora_mas_fallas' => $topLavadoras->first(),
            'componente_mayor_incidencia' => $componentes[0] ?? null,
            'dano_mas_frecuente' => $danos[0] ?? null,
            'top_lavadoras' => $topLavadoras->take(8)->all(),
            'top_componentes' => collect($componentes)->take(10)->all(),
            'top_danos' => collect($danos)->take(8)->all(),
        ];
    }

    private function resumirPeriodoLinea(Collection $eventos, array $componentes, array $danos, int $totalGlobal, Collection $cortes): array
    {
        $total = $eventos->count();
        $totalMensual = $this->construirSerieTotalMensual($eventos, $cortes);
        $ultimoMes = count($totalMensual) ? (int) $totalMensual[count($totalMensual) - 1] : 0;
        $mesAnterior = count($totalMensual) > 1 ? (int) $totalMensual[count($totalMensual) - 2] : 0;
        $variacionMensual = count($totalMensual) > 1 ? $this->calcularVariacion($mesAnterior, $ultimoMes) : null;
        $pico = null;

        if ($totalMensual) {
            $maximo = max($totalMensual);
            $indice = array_search($maximo, $totalMensual, true);
            $pico = [
                'periodo' => $cortes->values()->get($indice)['label'] ?? null,
                'total' => (int) $maximo,
            ];
        }

        return [
            'total_periodo' => $total,
            'componentes_afectados' => count($componentes),
            'tipos_dano' => count($danos),
            'porcentaje_total_global' => $this->porcentaje($total, $totalGlobal),
            'participacion_global' => $this->porcentaje($total, $totalGlobal),
            'componente_critico' => $componentes[0]['componente'] ?? null,
            'componente_critico_total' => $componentes[0]['total'] ?? 0,
            'dano_mas_frecuente' => $danos[0]['estado'] ?? null,
            'dano_mas_frecuente_total' => $danos[0]['total'] ?? 0,
            'ultimo_mes_total' => $ultimoMes,
            'mes_anterior_total' => $mesAnterior,
            'variacion_mensual' => $variacionMensual,
            'mes_pico' => $pico,
        ];
    }

    private function resumirComponentesPeriodo(Collection $eventos): array
    {
        if ($eventos->isEmpty()) {
            return [];
        }

        $total = $eventos->count();
        $mesActualInicio = now()->copy()->startOfMonth();
        $mesActualFin = now()->copy()->endOfDay();
        $mesAnteriorInicio = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $mesAnteriorFin = now()->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();

        return $eventos
            ->groupBy(fn (array $item) => $this->claveAgrupacion($item['componente'] ?? 'Sin componente'))
            ->map(function (Collection $grupo, string $key) use ($total, $mesActualInicio, $mesActualFin, $mesAnteriorInicio, $mesAnteriorFin) {
                $ordenados = $grupo->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())->values();
                $ultimo = $ordenados->first();
                $danos = $this->resumirDanosPeriodo($grupo);
                $ultimoTipoKey = $ultimo['type_key'] ?? $this->normalizarEstadoDano($ultimo['type_label'] ?? null);
                $ultimoDanoResumen = collect($danos)->firstWhere('key', $ultimoTipoKey);
                $actual = $this->contarEventosEnRango($grupo, $mesActualInicio, $mesActualFin);
                $anterior = $this->contarEventosEnRango($grupo, $mesAnteriorInicio, $mesAnteriorFin);

                return [
                    'key' => $key,
                    'componente' => $this->textoOValor($ultimo['componente'] ?? null, 'Sin componente'),
                    'codigo' => $ultimo['componente_codigo'] ?? null,
                    'total' => $grupo->count(),
                    'porcentaje' => $this->porcentaje($grupo->count(), $total),
                    'dano_principal' => $this->textoOValor($ultimo['type_label'] ?? null, $danos[0]['estado'] ?? 'Sin estado'),
                    'dano_principal_total' => (int) ($ultimoDanoResumen['total'] ?? ($danos[0]['total'] ?? 0)),
                    'danos' => $danos,
                    'ultima_falla' => $ultimo['fecha_humana'] ?? null,
                    'ubicaciones' => $this->resumirUbicaciones($grupo),
                    'mes_actual' => $actual,
                    'mes_anterior' => $anterior,
                    'delta_mes' => $actual - $anterior,
                ];
            })
            ->sort(function (array $a, array $b) {
                return ($b['total'] <=> $a['total']) ?: strcmp((string) $a['componente'], (string) $b['componente']);
            })
            ->values()
            ->all();
    }

    private function resumirDanosPeriodo(Collection $eventos): array
    {
        if ($eventos->isEmpty()) {
            return [];
        }

        $total = $eventos->count();

        return $eventos
            ->groupBy(fn (array $item) => $item['type_key'] ?? $this->normalizarEstadoDano($item['type_label'] ?? null))
            ->map(function (Collection $grupo, string $key) use ($total) {
                $ultimo = $grupo->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())->first();

                return [
                    'key' => $key,
                    'estado' => $this->textoOValor($ultimo['type_label'] ?? null, 'Sin estado'),
                    'total' => $grupo->count(),
                    'porcentaje' => $this->porcentaje($grupo->count(), $total),
                    'componentes_afectados' => $grupo
                        ->map(fn (array $item) => $this->claveAgrupacion($item['componente'] ?? 'Sin componente'))
                        ->unique()
                        ->count(),
                    'componentes' => $grupo
                        ->groupBy(fn (array $item) => $this->claveAgrupacion($item['componente'] ?? 'Sin componente'))
                        ->map(function (Collection $componentesGrupo) {
                            $ultimoComponente = $componentesGrupo
                                ->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())
                                ->first();

                            return [
                                'componente' => $this->textoOValor($ultimoComponente['componente'] ?? null, 'Sin componente'),
                                'total' => $componentesGrupo->count(),
                                'ubicaciones' => $this->resumirUbicaciones($componentesGrupo),
                            ];
                        })
                        ->sortByDesc('total')
                        ->values()
                        ->take(5)
                        ->all(),
                    'ultima_falla' => $ultimo['fecha_humana'] ?? null,
                ];
            })
            ->sort(function (array $a, array $b) {
                return ($b['total'] <=> $a['total']) ?: strcmp((string) $a['estado'], (string) $b['estado']);
            })
            ->values()
            ->all();
    }

    private function resumirUltimosDanosPorComponente(Collection $eventos): array
    {
        if ($eventos->isEmpty()) {
            return [];
        }

        $ultimos = $eventos
            ->groupBy(fn (array $item) => $this->claveUltimoDanoComponente($item))
            ->map(fn (Collection $grupo) => $grupo
                ->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())
                ->first())
            ->filter()
            ->values();

        if ($ultimos->isEmpty()) {
            return [];
        }

        $total = $ultimos->count();
        $historicoPorTipo = $eventos
            ->groupBy(fn (array $item) => $item['type_key'] ?? $this->normalizarEstadoDano($item['type_label'] ?? null))
            ->map(fn (Collection $grupo) => $grupo->count());

        return $ultimos
            ->groupBy(fn (array $item) => $item['type_key'] ?? $this->normalizarEstadoDano($item['type_label'] ?? null))
            ->map(function (Collection $grupo, string $key) use ($total, $historicoPorTipo) {
                $ultimo = $grupo->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())->first();

                return [
                    'key' => $key,
                    'estado' => $this->textoOValor($ultimo['type_label'] ?? null, 'Sin estado'),
                    'total' => $grupo->count(),
                    'porcentaje' => $this->porcentaje($grupo->count(), $total),
                    'componentes_afectados' => $grupo
                        ->map(fn (array $item) => $this->claveAgrupacion($item['componente'] ?? 'Sin componente'))
                        ->unique()
                        ->count(),
                    'componentes' => $grupo
                        ->groupBy(fn (array $item) => $this->claveAgrupacion($item['componente'] ?? 'Sin componente'))
                        ->map(function (Collection $componentesGrupo) {
                            $ultimoComponente = $componentesGrupo
                                ->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())
                                ->first();

                            return [
                                'componente' => $this->textoOValor($ultimoComponente['componente'] ?? null, 'Sin componente'),
                                'total' => $componentesGrupo->count(),
                                'ubicaciones' => $this->resumirUbicaciones($componentesGrupo),
                            ];
                        })
                        ->sortByDesc('total')
                        ->values()
                        ->take(5)
                        ->all(),
                    'ultima_falla' => $ultimo['fecha_humana'] ?? null,
                    'historial_total' => (int) ($historicoPorTipo->get($key, 0)),
                ];
            })
            ->sort(function (array $a, array $b) {
                return ($b['total'] <=> $a['total']) ?: strcmp((string) $a['estado'], (string) $b['estado']);
            })
            ->values()
            ->all();
    }

    private function claveUltimoDanoComponente(array $item): string
    {
        $componente = $this->claveAgrupacion($item['componente'] ?? 'Sin componente');
        $ubicacion = $this->claveAgrupacion($this->textoOValor(
            $item['ubicacion'] ?? $item['reductor'] ?? $item['lado'] ?? null,
            'Sin ubicacion'
        ));

        return implode('|', [
            $item['linea_id'] ?? 'sin-linea',
            $componente,
            $ubicacion,
        ]);
    }

    private function construirMatrizComponentesDanos(array $componentes): array
    {
        return collect($componentes)
            ->map(fn (array $item) => [
                'key' => $item['key'] ?? null,
                'componente' => $item['componente'] ?? 'Sin componente',
                'codigo' => $item['codigo'] ?? null,
                'total' => $item['total'] ?? 0,
                'porcentaje' => $item['porcentaje'] ?? 0,
                'dano_principal' => $item['dano_principal'] ?? null,
                'dano_principal_total' => $item['dano_principal_total'] ?? 0,
                'danos' => $item['danos'] ?? [],
                'ubicaciones' => $item['ubicaciones'] ?? [],
                'ultima_falla' => $item['ultima_falla'] ?? null,
                'delta_mes' => $item['delta_mes'] ?? 0,
            ])
            ->values()
            ->all();
    }

    private function construirSeriesComponentesMensuales(Collection $eventos, Collection $cortes, array $componentes, int $maxComponentes = 5): array
    {
        if ($eventos->isEmpty() || $cortes->isEmpty() || empty($componentes)) {
            return [];
        }

        $topComponentes = collect($componentes)->take($maxComponentes)->values();
        $topKeys = $topComponentes->pluck('key')->filter()->values()->all();
        $series = $topComponentes
            ->map(fn (array $componente) => [
                'key' => $componente['key'] ?? null,
                'label' => $componente['componente'] ?? 'Sin componente',
                'total' => $componente['total'] ?? 0,
                'porcentaje' => $componente['porcentaje'] ?? 0,
                'data' => $cortes
                    ->map(fn (array $corte) => $this->contarEventosEnMes($eventos, $corte['cut_at'], [$componente['key'] ?? null]))
                    ->all(),
            ])
            ->values();

        $otrosEventos = $eventos
            ->filter(fn (array $item) => !in_array($this->claveAgrupacion($item['componente'] ?? 'Sin componente'), $topKeys, true))
            ->values();

        if ($otrosEventos->isNotEmpty()) {
            $series->push([
                'key' => 'otros',
                'label' => 'Otros componentes',
                'total' => $otrosEventos->count(),
                'porcentaje' => $this->porcentaje($otrosEventos->count(), $eventos->count()),
                'data' => $cortes
                    ->map(fn (array $corte) => $this->contarEventosEnMes($otrosEventos, $corte['cut_at']))
                    ->all(),
            ]);
        }

        return $series->all();
    }

    private function construirSerieTotalMensual(Collection $eventos, Collection $cortes): array
    {
        if ($cortes->isEmpty()) {
            return [];
        }

        return $cortes
            ->map(fn (array $corte) => $this->contarEventosEnMes($eventos, $corte['cut_at']))
            ->all();
    }

    private function construirGraficasLinea(
        array $labels,
        array $componentes,
        array $danos,
        array $seriesComponentes,
        array $totalMensual,
        array $seriesVentanas,
        array $ventanas,
        Collection $cortes,
        Collection $eventosPeriodo
    ): array {
        $ventanaCorta = collect($seriesVentanas)->last() ?: [];
        $componentesTop = collect($componentes)->take(8)->values();
        $corteActual = $cortes->last()['cut_at'] ?? null;
        $resumenVentanas = collect($seriesVentanas)
            ->values()
            ->map(function (array $serie, int $index) use ($ventanas, $corteActual) {
                $ventana = $ventanas[$index] ?? [];
                $total = (float) collect($serie['data'] ?? [])->last(null, 0);
                $rango = $corteActual instanceof Carbon
                    ? $this->resolverRangoActual($ventana, $corteActual)
                    : null;
                $label = $serie['label'] ?? ($ventana['label'] ?? 'Ventana');
                $porcentaje = $this->porcentajePorCantidadDanos($total);

                return [
                    'label' => $label,
                    'total' => $total,
                    'porcentaje' => $porcentaje,
                    'desde' => $rango ? $rango['current_start']->format('d/m/Y') : null,
                    'hasta' => $rango ? $rango['current_end']->format('d/m/Y') : null,
                    'descripcion' => $this->descripcionVentana($label),
                    'escala' => '1 daño = 1%',
                ];
            })
            ->values();
        $componentKeysTop = $componentesTop->pluck('key')->filter()->values()->all();
        $eventosTopComponentes = $eventosPeriodo
            ->filter(fn (array $item) => in_array($this->claveAgrupacion($item['componente'] ?? 'Sin componente'), $componentKeysTop, true))
            ->values();
        $ubicacionesTopComponentes = $eventosTopComponentes
            ->groupBy(fn (array $item) => $this->claveAgrupacion($this->textoOValor($item['ubicacion'] ?? $item['reductor'] ?? $item['lado'] ?? null, 'Sin ubicacion')))
            ->map(function (Collection $grupo, string $key) {
                $ultimo = $grupo->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())->first();

                return [
                    'key' => $key,
                    'label' => $this->textoOValor($ultimo['ubicacion'] ?? $ultimo['reductor'] ?? $ultimo['lado'] ?? null, 'Sin ubicacion'),
                    'total' => $grupo->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values();
        $seriesDanosComponentes = $ubicacionesTopComponentes
            ->map(function (array $ubicacion) use ($componentesTop, $eventosTopComponentes) {
                $data = $componentesTop
                    ->map(function (array $componente) use ($ubicacion, $eventosTopComponentes) {
                        return $eventosTopComponentes
                            ->filter(fn (array $item) => $this->claveAgrupacion($item['componente'] ?? 'Sin componente') === ($componente['key'] ?? null))
                            ->filter(fn (array $item) => $this->claveAgrupacion($this->textoOValor($item['ubicacion'] ?? $item['reductor'] ?? $item['lado'] ?? null, 'Sin ubicacion')) === ($ubicacion['key'] ?? null))
                            ->count();
                    })
                    ->values()
                    ->all();
                $meta = $componentesTop
                    ->map(function (array $componente) use ($ubicacion, $eventosTopComponentes) {
                        $grupo = $eventosTopComponentes
                            ->filter(fn (array $item) => $this->claveAgrupacion($item['componente'] ?? 'Sin componente') === ($componente['key'] ?? null))
                            ->filter(fn (array $item) => $this->claveAgrupacion($this->textoOValor($item['ubicacion'] ?? $item['reductor'] ?? $item['lado'] ?? null, 'Sin ubicacion')) === ($ubicacion['key'] ?? null))
                            ->values();
                        $ultimo = $grupo
                            ->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())
                            ->first();
                        $danos = $this->resumirDanosPeriodo($grupo);

                        return [
                            'componente' => $componente['componente'] ?? 'Sin componente',
                            'ubicacion' => $ubicacion['label'] ?? 'Sin ubicacion',
                            'total' => $grupo->count(),
                            'ultimo_dano' => $ultimo['type_label'] ?? null,
                            'ultima_falla' => $ultimo['fecha_humana'] ?? null,
                            'danos' => collect($danos)->take(4)->values()->all(),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $ubicacion['key'] ?? null,
                    'label' => $ubicacion['label'] ?? 'Sin ubicacion',
                    'data' => $data,
                    'total' => array_sum($data),
                    'meta' => $meta,
                ];
            })
            ->filter(fn (array $serie) => (int) ($serie['total'] ?? 0) > 0)
            ->values();
        $ubicaciones = collect($componentes)
            ->flatMap(function (array $componente) {
                return collect($componente['ubicaciones'] ?? [])->map(function (array $ubicacion) {
                    $nombre = $this->textoOValor($ubicacion['ubicacion'] ?? null, 'Sin ubicacion');

                    return [
                        'key' => $this->claveAgrupacion($nombre),
                        'ubicacion' => $nombre,
                        'total' => (int) ($ubicacion['total'] ?? 0),
                        'componente' => $componente['componente'] ?? 'Sin componente',
                        'dano_principal' => $componente['dano_principal'] ?? null,
                    ];
                });
            })
            ->groupBy('key')
            ->map(function (Collection $grupo) {
                $primero = $grupo->first();

                return [
                    'ubicacion' => $primero['ubicacion'] ?? 'Sin ubicacion',
                    'total' => $grupo->sum('total'),
                    'componentes' => $grupo
                        ->groupBy(fn (array $item) => $this->claveAgrupacion($item['componente'] ?? 'Sin componente'))
                        ->map(function (Collection $componentesGrupo) {
                            $primeroComponente = $componentesGrupo->first();

                            return [
                                'componente' => $primeroComponente['componente'] ?? 'Sin componente',
                                'total' => $componentesGrupo->sum('total'),
                            ];
                        })
                        ->sortByDesc('total')
                        ->values()
                        ->take(5)
                        ->all(),
                    'danos' => $grupo
                        ->filter(fn (array $item) => filled($item['dano_principal'] ?? null))
                        ->groupBy(fn (array $item) => $this->claveAgrupacion($item['dano_principal'] ?? 'Sin daño'))
                        ->map(function (Collection $danosGrupo) {
                            $primeroDano = $danosGrupo->first();

                            return [
                                'estado' => $primeroDano['dano_principal'] ?? 'Sin daño',
                                'total' => $danosGrupo->sum('total'),
                            ];
                        })
                        ->sortByDesc('total')
                        ->values()
                        ->take(3)
                        ->all(),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values();

        return [
            'barras_componentes' => [
                'labels' => $labels,
                'series' => $seriesComponentes,
                'total_mensual' => $totalMensual,
                'descripcion' => 'Barras apiladas por mes: cada color representa un componente y la altura total muestra los daños del periodo.',
                'ventana_corta' => [
                    'label' => $ventanaCorta['label'] ?? null,
                    'data' => $ventanaCorta['data'] ?? [],
                    'descripcion' => 'Linea de comparacion rapida para detectar cambios recientes frente al historico visible.',
                ],
            ],
            'pastel_componentes' => [
                'labels' => collect($componentes)->take(6)->pluck('componente')->values()->all(),
                'data' => collect($componentes)->take(6)->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
                'porcentajes' => collect($componentes)->take(6)->pluck('porcentaje')->values()->all(),
                'descripcion' => 'Participacion de los componentes con mayor incidencia dentro del total de fallas.',
                'meta' => collect($componentes)->take(6)->map(fn (array $item) => [
                    'componente' => $item['componente'] ?? 'Sin componente',
                    'codigo' => $item['codigo'] ?? null,
                    'total' => (int) ($item['total'] ?? 0),
                    'porcentaje' => (float) ($item['porcentaje'] ?? 0),
                    'dano_principal' => $item['dano_principal'] ?? null,
                    'ultima_falla' => $item['ultima_falla'] ?? null,
                    'ubicaciones' => $item['ubicaciones'] ?? [],
                    'danos' => $item['danos'] ?? [],
                ])->values()->all(),
            ],
            'pastel_danos' => [
                'labels' => collect($danos)->take(6)->pluck('estado')->values()->all(),
                'data' => collect($danos)->take(6)->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
                'porcentajes' => collect($danos)->take(6)->pluck('porcentaje')->values()->all(),
                'descripcion' => 'Distribucion de los tipos de daño para identificar el patron dominante de falla.',
                'meta' => collect($danos)->take(6)->map(fn (array $item) => [
                    'estado' => $item['estado'] ?? 'Sin estado',
                    'total' => (int) ($item['total'] ?? 0),
                    'porcentaje' => (float) ($item['porcentaje'] ?? 0),
                    'componentes_afectados' => (int) ($item['componentes_afectados'] ?? 0),
                    'ultima_falla' => $item['ultima_falla'] ?? null,
                    'historial_total' => (int) ($item['historial_total'] ?? ($item['total'] ?? 0)),
                    'componentes' => $item['componentes'] ?? [],
                ])->values()->all(),
            ],
            'barras_componentes_totales' => [
                'labels' => collect($componentes)->take(8)->pluck('componente')->values()->all(),
                'data' => collect($componentes)->take(8)->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
                'principal' => collect($componentes)->take(8)->pluck('dano_principal')->values()->all(),
                'meta' => collect($componentes)->take(8)->map(fn (array $item) => [
                    'componente' => $item['componente'] ?? 'Sin componente',
                    'codigo' => $item['codigo'] ?? null,
                    'total' => (int) ($item['total'] ?? 0),
                    'porcentaje' => (float) ($item['porcentaje'] ?? 0),
                    'dano_principal' => $item['dano_principal'] ?? null,
                    'ultima_falla' => $item['ultima_falla'] ?? null,
                    'ubicaciones' => $item['ubicaciones'] ?? [],
                    'danos' => $item['danos'] ?? [],
                ])->values()->all(),
                'descripcion' => 'Ranking de componentes por cantidad acumulada de daños en el periodo analizado.',
            ],
            'barras_danos_componentes' => [
                'labels' => $componentesTop->pluck('componente')->values()->all(),
                'meta' => [
                    'componentes' => $componentesTop->map(fn (array $item) => [
                        'componente' => $item['componente'] ?? 'Sin componente',
                        'codigo' => $item['codigo'] ?? null,
                        'total' => (int) ($item['total'] ?? 0),
                        'porcentaje' => (float) ($item['porcentaje'] ?? 0),
                        'dano_principal' => $item['dano_principal'] ?? null,
                        'ultima_falla' => $item['ultima_falla'] ?? null,
                        'ubicaciones' => $item['ubicaciones'] ?? [],
                    ])->values()->all(),
                ],
                'series' => $seriesDanosComponentes->map(fn (array $serie) => [
                    'key' => $serie['key'] ?? null,
                    'label' => $serie['label'] ?? 'Sin ubicacion',
                    'data' => $serie['data'] ?? [],
                    'meta' => $serie['meta'] ?? [],
                ])->values()->all(),
                'descripcion' => 'Barras apiladas de fallas: reductores o ubicaciones al lateral, componentes abajo por color y conteo de daños en cada cruce con detalle en el tooltip.',
            ],
            'barras_ubicaciones' => [
                'labels' => $ubicaciones->pluck('ubicacion')->values()->all(),
                'data' => $ubicaciones->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
                'meta' => $ubicaciones->map(fn (array $item) => [
                    'ubicacion' => $item['ubicacion'] ?? 'Sin ubicacion',
                    'total' => (int) ($item['total'] ?? 0),
                    'componentes' => $item['componentes'] ?? [],
                    'danos' => $item['danos'] ?? [],
                ])->values()->all(),
                'descripcion' => 'Ranking de ubicaciones o reductores con mas fallas acumuladas.',
            ],
            'barras_ventanas' => [
                'labels' => $resumenVentanas->pluck('label')->values()->all(),
                'data' => $resumenVentanas->pluck('porcentaje')->values()->all(),
                'totales' => $resumenVentanas->pluck('total')->values()->all(),
                'meta' => $resumenVentanas->all(),
                'series' => collect($seriesVentanas)->map(fn (array $serie) => [
                    'label' => $serie['label'] ?? 'Ventana',
                    'data' => collect($serie['data'] ?? [])->map(fn ($value) => (float) $value)->values()->all(),
                ])->values()->all(),
                'descripcion' => 'Compara las ventanas acumuladas hacia atras usando escala directa: 1 daño equivale a 1% y el maximo visual es 100%.',
            ],
        ];
    }

    private function construirGraficasGlobal(array $global): array
    {
        return [
            'barras_lavadoras' => [
                'labels' => collect($global['top_lavadoras'] ?? [])->pluck('linea')->values()->all(),
                'data' => collect($global['top_lavadoras'] ?? [])->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
                'porcentajes' => collect($global['top_lavadoras'] ?? [])->pluck('porcentaje')->values()->all(),
                'descripcion' => 'Ranking de lavadoras con mayor numero de fallas en el periodo visible.',
            ],
            'pastel_componentes' => [
                'labels' => collect($global['top_componentes'] ?? [])->take(8)->pluck('componente')->values()->all(),
                'data' => collect($global['top_componentes'] ?? [])->take(8)->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
                'porcentajes' => collect($global['top_componentes'] ?? [])->take(8)->pluck('porcentaje')->values()->all(),
                'descripcion' => 'Participacion global de los componentes que concentran mas fallas.',
            ],
            'pastel_danos' => [
                'labels' => collect($global['top_danos'] ?? [])->take(8)->pluck('estado')->values()->all(),
                'data' => collect($global['top_danos'] ?? [])->take(8)->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
                'porcentajes' => collect($global['top_danos'] ?? [])->take(8)->pluck('porcentaje')->values()->all(),
                'descripcion' => 'Distribucion global de los tipos de daño mas frecuentes.',
            ],
        ];
    }

    private function contarEventosEnMes(Collection $eventos, Carbon $corte, ?array $componentKeys = null): int
    {
        $inicio = $corte->copy()->startOfMonth()->startOfDay();
        $fin = $corte->copy()->endOfDay();
        $componentKeys = $componentKeys ? array_filter($componentKeys) : null;

        return $eventos
            ->filter(function (array $item) use ($inicio, $fin, $componentKeys) {
                $fecha = $item['occurred_at'] ?? null;

                if (!$fecha instanceof Carbon || !$fecha->between($inicio, $fin, true)) {
                    return false;
                }

                if (!$componentKeys) {
                    return true;
                }

                return in_array($this->claveAgrupacion($item['componente'] ?? 'Sin componente'), $componentKeys, true);
            })
            ->count();
    }

    private function resumirUbicaciones(Collection $eventos): array
    {
        return $eventos
            ->pluck('ubicacion')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->map(fn (int $total, string $ubicacion) => [
                'ubicacion' => $ubicacion,
                'total' => $total,
            ])
            ->values()
            ->take(4)
            ->all();
    }

    private function textoOValor($value, string $fallback): string
    {
        $texto = trim((string) $value);

        return $texto !== '' ? $texto : $fallback;
    }

    private function claveAgrupacion($value): string
    {
        $clave = Str::of((string) $value)->ascii()->lower()->squish()->replace(' ', '-')->value();

        return $clave !== '' ? $clave : 'sin-componente';
    }

    private function porcentaje(int|float $valor, int|float $total): float
    {
        if ((float) $total <= 0.0) {
            return 0.0;
        }

        return round(((float) $valor / (float) $total) * 100, 1);
    }

    private function porcentajePorCantidadDanos(int|float $total): float
    {
        return round(min(max((float) $total, 0.0), 100.0), 1);
    }

    private function descripcionVentana(?string $label): string
    {
        $normalizado = Str::of((string) $label)->ascii()->lower()->value();

        if (str_contains($normalizado, '52')) {
            return '52 semanas equivalen a 1 ano hacia atras desde el corte actual.';
        }

        if (str_contains($normalizado, '12')) {
            return '12 semanas equivalen a 3 meses hacia atras desde el corte actual.';
        }

        if (str_contains($normalizado, '30')) {
            return '30 dias hacia atras desde el dia actual o corte seleccionado.';
        }

        if (str_contains($normalizado, '14')) {
            return '14 dias hacia atras desde el dia actual o corte seleccionado.';
        }

        if (str_contains($normalizado, '7')) {
            return '7 dias hacia atras desde el dia actual o corte seleccionado.';
        }

        if (str_contains($normalizado, '4')) {
            return '4 semanas equivalen a 1 mes hacia atras desde el corte actual.';
        }

        return 'Lapso acumulado hacia atras desde el corte actual.';
    }

    private function contarEventosEnVentana(Collection $eventos, array $ventana, Carbon $referencia): int
    {
        $rango = $this->resolverRangoActual($ventana, $referencia);

        return $this->contarEventosEnRango($eventos, $rango['current_start'], $rango['current_end']);
    }

    private function contarEventosEnRango(Collection $eventos, Carbon $inicio, Carbon $fin): int
    {
        return $eventos
            ->filter(fn (array $item) => $item['occurred_at']->between($inicio, $fin, true))
            ->count();
    }

    private function resolverRangosVentana(array $ventana, Carbon $referencia): array
    {
        $actual = $this->resolverRangoActual($ventana, $referencia);
        $size = max((int) ($ventana['size'] ?? 1), 1);

        if (($ventana['type'] ?? 'days') === 'weeks') {
            $inicioAnterior = $actual['current_start']->copy()->subWeeks($size);
            $finAnterior = $actual['current_end']->copy()->subWeeks($size);
        } else {
            $inicioAnterior = $actual['current_start']->copy()->subDays($size);
            $finAnterior = $actual['current_end']->copy()->subDays($size);
        }

        return [
            'current_start' => $actual['current_start'],
            'current_end' => $actual['current_end'],
            'previous_start' => $inicioAnterior,
            'previous_end' => $finAnterior,
        ];
    }

    private function resolverRangoActual(array $ventana, Carbon $referencia): array
    {
        $fin = $referencia->copy()->endOfDay();
        $size = max((int) ($ventana['size'] ?? 1), 1);

        if (($ventana['type'] ?? 'days') === 'weeks') {
            $inicio = $fin->copy()->subWeeks($size)->addDay()->startOfDay();
        } else {
            $inicio = $fin->copy()->subDays($size - 1)->startOfDay();
        }

        return [
            'current_start' => $inicio,
            'current_end' => $fin,
        ];
    }

    private function calcularVariacion($anterior, $actual): ?array
    {
        if ($anterior === null) {
            return null;
        }

        $anterior = (float) $anterior;
        $actual = (float) $actual;
        $diferencia = $actual - $anterior;
        $porcentaje = $anterior == 0.0
            ? ($actual > 0 ? 100.0 : 0.0)
            : round(($diferencia / $anterior) * 100, 2);
        $tendencia = $diferencia > 0 ? 'up' : ($diferencia < 0 ? 'down' : 'stable');

        return [
            'diferencia' => round($diferencia, 2),
            'porcentaje' => $porcentaje,
            'tendencia' => $tendencia,
        ];
    }

    private function resolverEstado(Collection $ventanas): array
    {
        if ($ventanas->isEmpty() || $ventanas->every(fn (array $item) => (int) $item['current'] === 0)) {
            return ['label' => 'Sin fallas', 'tone' => 'success'];
        }

        $up = $ventanas->where('trend', 'up')->count();
        $down = $ventanas->where('trend', 'down')->count();

        if ($up > $down) {
            return ['label' => 'Acelerando', 'tone' => 'danger'];
        }

        if ($down > $up) {
            return ['label' => 'En descenso', 'tone' => 'success'];
        }

        if ($up === 0 && $down === 0) {
            return ['label' => 'Estable', 'tone' => 'info'];
        }

        return ['label' => 'Mixto', 'tone' => 'warning'];
    }

    private function resolverTono(string $trend, int $actual): string
    {
        return match ($trend) {
            'up' => 'danger',
            'down' => 'success',
            default => $actual > 0 ? 'warning' : 'info',
        };
    }

    private function esEstadoDano(?string $estado): bool
    {
        $normalizado = $this->normalizarEstadoDano($estado);

        return in_array($normalizado, self::DAMAGE_STATES, true);
    }

    private function normalizarEstadoDano(?string $estado): string
    {
        $estado = trim((string) $estado);

        $estado = strtr($estado, [
            'DaÃ±ado' => 'Danado',
            'DaÃƒÂ±ado' => 'Danado',
            'DaÃƒÆ’Ã‚Â±ado' => 'Danado',
            'Dañado' => 'Danado',
            'Daño' => 'Dano',
            'daño' => 'dano',
        ]);

        return Str::of($estado)->ascii()->lower()->squish()->value();
    }

    private function normalizarLado(?string $lado): ?string
    {
        $normalizado = Str::of((string) $lado)->ascii()->upper()->squish()->value();

        if ($normalizado === '') {
            return null;
        }

        if (str_contains($normalizado, 'VAPOR')) {
            return 'VAPOR';
        }

        if (str_contains($normalizado, 'PASILLO')) {
            return 'PASILLO';
        }

        return null;
    }

    private function contarEventosPorLado(Collection $eventos): array
    {
        $lados = $eventos
            ->pluck('lado')
            ->filter()
            ->values();

        if ($lados->isEmpty()) {
            return [];
        }

        $conteo = [
            'VAPOR' => $lados->filter(fn (string $lado) => $lado === 'VAPOR')->count(),
            'PASILLO' => $lados->filter(fn (string $lado) => $lado === 'PASILLO')->count(),
        ];

        return array_sum($conteo) > 0 ? $conteo : [];
    }

    private function formatearEventosVentana(Collection $eventos): array
    {
        return $eventos
            ->sortByDesc(fn (array $item) => $item['occurred_at']->getTimestamp())
            ->map(fn (array $item) => [
                'id' => $item['id'] ?? null,
                'fecha' => $item['fecha_humana'] ?? null,
                'linea_id' => $item['linea_id'] ?? null,
                'linea' => $item['linea'] ?? null,
                'componente' => $item['componente'] ?? null,
                'componente_codigo' => $item['componente_codigo'] ?? null,
                'reductor' => $item['reductor'] ?? null,
                'modulo' => $item['modulo'] ?? null,
                'nivel' => $item['nivel'] ?? null,
                'lado' => $item['lado'] ?? null,
                'ubicacion' => $item['ubicacion'] ?? null,
                'estado' => $item['type_label'] ?? null,
                'estado_key' => $item['type_key'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function normalizarTipo(string $tipoEquipo): string
    {
        return $tipoEquipo === self::TIPO_PASTEURIZADORAS || $tipoEquipo === 'pasteurizadora'
            ? self::TIPO_PASTEURIZADORAS
            : self::TIPO_LAVADORAS;
    }

    private function formatearRango(Carbon $inicio, Carbon $fin): string
    {
        return $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y');
    }

    private function nombreMes(int $mes): string
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ][$mes] ?? (string) $mes;
    }

    private function nombreMesCorto(int $mes): string
    {
        return [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ][$mes] ?? (string) $mes;
    }
}
