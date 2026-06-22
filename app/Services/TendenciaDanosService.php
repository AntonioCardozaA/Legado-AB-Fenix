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
            'Danado - Requiere cambio',
            'Danado - Cambiado',
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
            ];
        }

        $rangoFuente = $rangoVisible ? $this->ampliarRangoFuente($rangoVisible, $ventanas) : null;
        $eventos = $this->obtenerEventos($lineas, $tipoEquipo, $rangoFuente)->values();
        $eventosPorLinea = $eventos->groupBy('linea_id');
        $cortes = $this->construirCortesMensuales($eventos, $rangoVisible);
        $labels = $cortes->pluck('label')->all();

        $seriesPorLinea = $lineas
            ->map(function (Linea $linea) use ($eventosPorLinea, $ventanas, $cortes, $labels) {
                $eventosLinea = $eventosPorLinea
                    ->get($linea->id, collect())
                    ->sortBy(fn (array $item) => $item['occurred_at']->getTimestamp())
                    ->values();

                $series = collect($ventanas)
                    ->map(function (array $ventana) use ($eventosLinea, $cortes) {
                        return [
                            'key' => $ventana['key'],
                            'label' => $ventana['label'],
                            'data' => $cortes
                                ->map(fn (array $corte) => $this->contarEventosEnVentana($eventosLinea, $ventana, $corte['cut_at']))
                                ->all(),
                        ];
                    })
                    ->values();

                $ultimoEvento = $eventosLinea->last();
                $ultimoCorte = $labels ? end($labels) : null;
                $resumenSeries = $series
                    ->mapWithKeys(fn (array $serie) => [$serie['key'] => (int) collect($serie['data'])->last()])
                    ->all();

                return [
                    'linea_id' => $linea->id,
                    'linea' => $linea->nombre,
                    'labels' => $labels,
                    'series' => $series->all(),
                    'resumen' => [
                        'ultimo_corte' => $ultimoCorte,
                        'ultima_falla' => $ultimoEvento['fecha_humana'] ?? null,
                        'ultima_fuente' => $ultimoEvento['type_label'] ?? null,
                        'total_fallas' => $eventosLinea->count(),
                    ] + $resumenSeries,
                    'ventanas' => $labels
                        ? $this->resumirLinea($eventosLinea, $ventanas, $cortes->last()['cut_at'])['ventanas']
                        : $this->resumenVacio($ventanas)['ventanas'],
                    'sin_datos' => $eventosLinea->isEmpty(),
                ] + $series->mapWithKeys(fn (array $serie) => [$serie['key'] => $serie['data']])->all();
            })
            ->values();

        $default = $seriesPorLinea->firstWhere('sin_datos', false);

        return [
            'default_linea_id' => $default['linea_id'] ?? $lineas->first()?->id,
            'lineas' => $seriesPorLinea->all(),
            'criterios' => $this->criteriosDano(),
        ];
    }

    public function construirFilasMensuales(Linea $linea, string $tipoEquipo, int $meses = 12, ?Carbon $fin = null): Collection
    {
        $fin = ($fin ?: now())->copy()->endOfMonth()->endOfDay();
        $inicio = $fin->copy()->subMonthsNoOverflow(max($meses - 1, 0))->startOfMonth();
        $inicioFuente = collect($this->ventanas52124())
            ->merge($this->ventanas30147())
            ->map(fn (array $ventana) => $this->resolverRangoActual($ventana, $inicio->copy()->endOfMonth())['current_start'])
            ->sortBy(fn (Carbon $fecha) => $fecha->getTimestamp())
            ->first();

        $eventos = $this->obtenerEventos(collect([$linea]), $tipoEquipo, [
            'from' => $inicioFuente,
            'to' => $fin,
        ])->where('linea_id', $linea->id)->values();

        $filas = collect();
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            $corte = $cursor->copy()->endOfMonth()->endOfDay();
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

                    return [
                        'id' => $item->id,
                        'source' => 'componente',
                        'type_label' => $item->estado,
                        'linea_id' => $item->linea_id,
                        'linea' => $item->linea?->nombre ?? $lineaNombres->get($item->linea_id),
                        'componente' => $item->componente,
                        'modulo' => $item->modulo,
                        'nivel' => $item->nivel,
                        'lado' => $this->normalizarLado($item->lado),
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

                return [
                    'id' => $item->id,
                    'source' => 'componente',
                    'type_label' => $item->estado,
                    'linea_id' => $item->linea_id,
                    'linea' => $item->linea?->nombre ?? $lineaNombres->get($item->linea_id),
                    'componente' => $item->componente?->nombre ?? $item->componente?->codigo,
                    'reductor' => $item->reductor,
                    'lado' => $this->normalizarLado($item->lado),
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
                'reductor' => $item['reductor'] ?? null,
                'modulo' => $item['modulo'] ?? null,
                'nivel' => $item['nivel'] ?? null,
                'lado' => $item['lado'] ?? null,
                'estado' => $item['type_label'] ?? null,
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
