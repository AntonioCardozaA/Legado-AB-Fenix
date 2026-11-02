<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class PasteurizadoraHistoryPages
{
    public const METADATA_COLUMNS = [
        'id', 'linea_id', 'area', 'modulo', 'componente', 'lado', 'nivel',
        'tipo_registro', 'estado', 'total_componentes', 'componentes_revisados',
        'cantidad_componentes_revisados', 'fecha_analisis', 'created_at',
    ];

    /** Group totals use the complete metadata; only visible records load text, evidence and users. */
    public function build(Collection $analisis, Request $request, callable $loadRecords, int $perPage = 25): array
    {
        $agrupados = [];

        foreach($analisis as $item) {
            $key = $item->linea_id . '|' . $item->modulo . '|' . $item->componente . '|' . ($item->lado ?? 'SIN_LADO') . '|' . ($item->nivel ?? 'SIN_NIVEL');

            if (!isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'linea_nombre' => $item->linea->nombre ?? 'N/A',
                    'modulo' => (int) $item->modulo,
                    'componente_nombre' => $item->componente_nombre ?? 'N/A',
                    'lado' => $item->lado,
                    'nivel' => $item->nivel,
                    'registros' => [],
                    'total_componentes' => (int) $item->total_componentes,
                    'todas_componentes_revisadas' => [],
                    'fecha_inicio' => null,
                    'fecha_fin' => null,
                    'fecha_ultima_actualizacion' => null,
                    'total_registros' => 0
                ];
            }

            $agrupados[$key]['registros'][] = $item;
            $agrupados[$key]['total_registros']++;

            $componentes = $item->componentes_revisados_lista;

            // Acumular componentes revisadas
            foreach ($componentes as $num) {
                if (is_numeric($num) && (int)$num > 0) {
                    $agrupados[$key]['todas_componentes_revisadas'][] = (int)$num;
                }
            }

            $agrupados[$key]['todas_componentes_revisadas'] = array_unique($agrupados[$key]['todas_componentes_revisadas']);

            // Fechas
            $fecha = $item->fecha_analisis ?? $item->created_at;
            if ($fecha) {
                if (!$agrupados[$key]['fecha_inicio'] || $fecha < $agrupados[$key]['fecha_inicio']) {
                    $agrupados[$key]['fecha_inicio'] = $fecha;
                }
                if (!$agrupados[$key]['fecha_fin'] || $fecha > $agrupados[$key]['fecha_fin']) {
                    $agrupados[$key]['fecha_fin'] = $fecha;
                }
            }

            $fechaActualizacion = $item->created_at ?? $fecha;
            if ($fechaActualizacion) {
                if (
                    !$agrupados[$key]['fecha_ultima_actualizacion'] ||
                    $fechaActualizacion > $agrupados[$key]['fecha_ultima_actualizacion']
                ) {
                    $agrupados[$key]['fecha_ultima_actualizacion'] = $fechaActualizacion;
                }
            }
        }

        foreach ($agrupados as &$grupoOrdenado) {
            usort($grupoOrdenado['registros'], function ($a, $b) {
                $valorA = ($a->created_at?->timestamp ?? 0) . '-' . str_pad((string) $a->id, 10, '0', STR_PAD_LEFT);
                $valorB = ($b->created_at?->timestamp ?? 0) . '-' . str_pad((string) $b->id, 10, '0', STR_PAD_LEFT);
                return strcmp($valorB, $valorA);
            });
        }
        unset($grupoOrdenado);

        // Ordenar grupos por última actualización descendente
        uasort($agrupados, function($a, $b) {
            return strtotime((string) $b['fecha_ultima_actualizacion']) - strtotime((string) $a['fecha_ultima_actualizacion']);
        });

        // Estadísticas rápidas

        $visibleIds = collect();
        foreach ($agrupados as $key => &$grupo) {
            $pageName = 'historial_' . substr(hash('sha256', $key), 0, 12) . '_page';
            $page = filter_var($request->query($pageName, 1), FILTER_VALIDATE_INT);
            $page = $page !== false && $page > 0 ? $page : 1;
            $records = collect($grupo['registros']);
            $slice = $records->forPage($page, $perPage)->values();
            $grupo['paginador'] = (new LengthAwarePaginator($slice, $records->count(), $perPage, $page, [
                'path' => $request->url(), 'query' => $request->query(), 'pageName' => $pageName,
            ]))->fragment($pageName);
            $visibleIds = $visibleIds->merge($slice->pluck('id'));
        }
        unset($grupo);

        $fullRecords = $visibleIds->isEmpty() ? collect() : $loadRecords($visibleIds->unique()->values())->keyBy('id');
        foreach ($agrupados as &$grupo) {
            $paginator = $grupo['paginador'];
            $paginator->setCollection($paginator->getCollection()
                ->map(fn ($record) => $fullRecords->get($record->id))->filter()->values());
            $grupo['registros'] = $paginator->getCollection();
        }
        unset($grupo);

        $gruposOrdenados = collect($agrupados);
        $historialSecciones = collect([
            'VAPOR' => [
                'titulo' => 'Lado Vapor',
                'icono' => 'fa-cloud',
            ],
            'PASILLO' => [
                'titulo' => 'Lado Pasillo',
                'icono' => 'fa-walking',
            ],
        ])->map(function (array $seccion, string $lado) use ($gruposOrdenados): array {
            $grupos = $gruposOrdenados
                ->filter(fn (array $grupo): bool => strtoupper(trim((string) ($grupo['lado'] ?? ''))) === $lado)
                ->values();

            return array_merge($seccion, [
                'key' => $lado,
                'grupos' => $grupos,
                'total' => $grupos->sum(fn (array $grupo): int => (int) ($grupo['total_registros'] ?? count($grupo['registros'] ?? []))),
            ]);
        })->values();

        $gruposSinLado = $gruposOrdenados
            ->filter(fn (array $grupo): bool => !in_array(strtoupper(trim((string) ($grupo['lado'] ?? ''))), ['VAPOR', 'PASILLO'], true))
            ->values();

        if ($gruposSinLado->isNotEmpty()) {
            $historialSecciones->push([
                'key' => 'SIN_LADO',
                'titulo' => 'Sin lado registrado',
                'icono' => 'fa-question-circle',
                'grupos' => $gruposSinLado,
                'total' => $gruposSinLado->sum(fn (array $grupo): int => (int) ($grupo['total_registros'] ?? count($grupo['registros'] ?? []))),
            ]);
        }

        return [
            'totalAnalisis' => $analisis->count(),
            'analisis' => $analisis->map(fn ($record) => $fullRecords->get($record->id))->filter()->values(),
            'historialSecciones' => $historialSecciones,
        ];
    }
}
